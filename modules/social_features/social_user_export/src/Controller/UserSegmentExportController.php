<?php

declare(strict_types=1);

namespace Drupal\social_user_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\social_user_export\Plugin\Action\ExportUser;
use Drupal\user_segments\Entity\UserSegment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for exporting user segments to CSV.
 *
 * Uses ExportUser action instance to handle the export, similar to how
 * ExportMember exports group members. Gets user IDs from the segment and
 * delegates export processing to ExportUser.
 */
final class UserSegmentExportController extends ControllerBase {

  /**
   * Constructs a UserSegmentExportController object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   */
  public function __construct(
    protected LoggerChannelInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new self(
      $container->get('logger.factory')->get('user_segments')
    );
    // Set messenger from parent ControllerBase.
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * Exports a user segment to CSV.
   *
   * Gets user IDs from the segment and uses ExportUser instance to process
   * the export through batch operations.
   *
   * @param \Drupal\user_segments\Entity\UserSegment $user_segment
   *   The user segment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   A redirect response or batch processing response.
   */
  public function exportCsv(UserSegment $user_segment): RedirectResponse|Response {
    try {
      // Get user IDs from the segment.
      $storage = $this->entityTypeManager()->getStorage('user_segment');
      $user_ids = $storage->getUserIdsInSegment($user_segment);

      // Early return if no users found.
      if (empty($user_ids)) {
        $this->messenger()->addWarning($this->t('The selected user segment does not contain any users to export.'));
        return $this->redirect('entity.user_segment.canonical', [
          'user_segment' => $user_segment->id(),
        ]);
      }

      // Load all users.
      $user_storage = $this->entityTypeManager()->getStorage('user');
      $users = $user_storage->loadMultiple($user_ids);

      // Create ExportUser action instance.
      $plugin_manager = \Drupal::service('plugin.manager.action');
      $action_configuration = [];
      $plugin_id = 'social_user_export_user_action';
      $plugin_definition = $plugin_manager->getDefinition($plugin_id);
      $export_user = ExportUser::create(\Drupal::getContainer(), $action_configuration, $plugin_id, $plugin_definition);

      // Set up VBO-style context (ExportUser expects this structure).
      $batch_size = 50;
      $total = count($users);
      $context = [
        'sandbox' => [
          'current_batch' => 1,
          'batch_size' => $batch_size,
          'total' => $total,
          'results' => [],
        ],
      ];
      $export_user->setContext($context);

      // Delegate to ExportUser - it handles CSV creation, headers, writing,
      // and file saving.
      $export_user->executeMultiple($users);

      // ExportUser displays the success message, so just redirect.
      return $this->redirect('entity.user_segment.collection');
    }
    catch (\Exception $e) {
      $this->logger->error('Error exporting user segment @id', [
        '@id' => $user_segment->id(),
        'exception' => $e,
      ]);
      $this->messenger()->addError($this->t('An error occurred while exporting the user segment.'));
      return $this->redirect('entity.user_segment.canonical', [
        'user_segment' => $user_segment->id(),
      ]);
    }
  }

}

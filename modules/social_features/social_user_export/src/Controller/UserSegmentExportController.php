<?php

declare(strict_types=1);

namespace Drupal\social_user_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\social_user_export\UserExportService;
use Drupal\user_segments\Entity\UserSegment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for exporting user segments to CSV.
 *
 * Uses UserExportService to handle the export. Gets user IDs from the segment
 * and delegates export processing to the VBO-agnostic export service.
 */
final class UserSegmentExportController extends ControllerBase {

  /**
   * Constructs a UserSegmentExportController object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   * @param \Drupal\social_user_export\UserExportService $exportService
   *   The user export service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    protected LoggerChannelInterface $logger,
    protected UserExportService $exportService,
    MessengerInterface $messenger,
  ) {
    // Set messenger from MessengerTrait used by parent ControllerBase.
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('logger.channel.social_user_export'),
      $container->get('social_user_export.user_export'),
      $container->get('messenger')
    );
  }

  /**
   * Exports a user segment to CSV.
   *
   * Gets user IDs from the segment and uses UserExportService to process
   * the export through Drupal's Batch API.
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

      // Delegate to the VBO-agnostic export service. It handles CSV creation,
      // batch processing, and file saving. Do NOT load entities into memory -
      // the service will load users in batches.
      // Pass 'segment-users' as an export type so the filename matches the
      // permission check pattern in hook_file_download().
      $redirectUrl = Url::fromRoute('entity.user_segment.collection');
      return $this->exportService->exportUsers($user_ids, $redirectUrl, 'segment-users') ?? $this->redirect('entity.user_segment.collection');
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

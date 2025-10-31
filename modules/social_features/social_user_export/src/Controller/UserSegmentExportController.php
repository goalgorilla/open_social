<?php

declare(strict_types=1);

namespace Drupal\social_user_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\user_segments\Entity\UserSegment;
use Drupal\social_user_export\UserSegmentExportService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides user segment CSV export functionality.
 *
 * Uses batch processing to handle large datasets and save files to the
 * private file system with access control for maximum security.
 */
final class UserSegmentExportController extends ControllerBase {

  /**
   * Constructs a UserSegmentExportController object.
   *
   * @param \Drupal\social_user_export\UserSegmentExportService $exportService
   *   The export service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   */
  public function __construct(
    protected UserSegmentExportService $exportService,
    protected LoggerChannelInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new self(
      $container->get('user_segments.export'),
      $container->get('logger.factory')->get('user_segments')
    );
    // Set messenger from parent ControllerBase.
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * Exports a user segment to CSV.
   *
   * This delegates all business logic to the export service and handles
   * only HTTP concerns (responses, redirects, messaging).
   *
   * Access control is handled at the route level via entity access check,
   * so disabled segments are already filtered out before reaching this method.
   *
   * @param \Drupal\user_segments\Entity\UserSegment $user_segment
   *   The user segment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   A redirect response or batch processing response.
   */
  public function exportCsv(UserSegment $user_segment): RedirectResponse|Response {
    try {
      // Delegate all business logic to the service.
      $result = $this->exportService->initiateExport($user_segment);

      // Handle case where export cannot proceed (e.g., no users).
      if ($result['batch'] === NULL) {
        if ($result['message'] !== NULL) {
          $this->messenger()->addWarning($result['message']);
        }
        return $this->redirect($result['redirect_route'] ?? 'entity.user_segment.collection', [
          'user_segment' => $user_segment->id(),
        ]);
      }

      // Start batch processing.
      batch_set($result['batch']);
      $batch_response = batch_process(Url::fromRoute('entity.user_segment.collection'));
      return $batch_response ?? $this->redirect('entity.user_segment.collection');
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

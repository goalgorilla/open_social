<?php

declare(strict_types=1);

namespace Drupal\social_user_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\user_segments\Entity\UserSegment;
use Drupal\social_user_export\UserSegmentExportService;
use League\Csv\Writer;
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
   * This creates a batch job that generates the CSV file in the background
   * and saves it to the private file system for secure, access-controlled
   * download.
   *
   * @param \Drupal\user_segments\Entity\UserSegment $user_segment
   *   The user segment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   A redirect response or batch processing response.
   */
  public function exportCsv(UserSegment $user_segment): RedirectResponse|Response {
    // Validate segment.
    $validation = $this->exportService->validateSegment($user_segment);
    if (!$validation['valid']) {
      $this->messenger()->addError($validation['message']);
      return $this->redirect('entity.user_segment.canonical', [
        'user_segment' => $user_segment->id(),
      ]);
    }

    try {
      // Get user IDs from the segment using the storage API.
      $user_ids = $this->entityTypeManager()->getStorage('user_segment')->getUserIdsInSegment($user_segment);

      // Early return if no users found.
      if (empty($user_ids)) {
        $this->messenger()->addWarning($this->t('The selected user segment does not contain any users to export.'));
        return $this->redirect('entity.user_segment.canonical', [
          'user_segment' => $user_segment->id(),
        ]);
      }

      // Create batch using service.
      $batch = $this->exportService->createBatch($user_segment, $user_ids);
      batch_set($batch);

      $batch_response = batch_process(Url::fromRoute('entity.user_segment.collection'));
      return $batch_response ?? $this->redirect('entity.user_segment.collection');
    }
    catch (\Exception $e) {
      $this->logger->error('Error exporting user segment @id: @message', [
        '@id' => $user_segment->id(),
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('An error occurred while exporting the user segment.'));
      return $this->redirect('entity.user_segment.canonical', [
        'user_segment' => $user_segment->id(),
      ]);
    }
  }

  /**
   * Batch operation: Processes a batch of users.
   *
   * @param int $batch_number
   *   The batch number.
   * @param array $user_ids
   *   Array of user IDs to process.
   * @param string $filename
   *   The filename for the export.
   * @param array $plugin_definitions
   *   The export plugin definitions.
   * @param string $segment_label
   *   The segment label for messaging.
   * @param array $context
   *   The batch context.
   */
  public static function processBatch(int $batch_number, array $user_ids, string $filename, array $plugin_definitions, string $segment_label, array &$context): void {
    $file_system = \Drupal::service('file_system');
    $entity_type_manager = \Drupal::entityTypeManager();
    $user_export_plugin = \Drupal::service('plugin.manager.user_export_plugin');

    // Initialize a file path in results.
    if (!isset($context['results']['file_path'])) {
      $context['results']['file_path'] = $file_system->getTempDirectory() . DIRECTORY_SEPARATOR . $filename;
      $context['results']['segment_label'] = $segment_label;

      // Create CSV and add headers.
      $csv = Writer::createFromPath($context['results']['file_path'], 'w');
      $csv->setDelimiter(',');
      $csv->setEnclosure('"');
      $csv->setEscape('\\');

      $headers = [];
      foreach ($plugin_definitions as $plugin_definition) {
        /** @var \Drupal\social_user_export\Plugin\UserExportPluginInterface $plugin_instance */
        $plugin_instance = $user_export_plugin->createInstance($plugin_definition['id']);
        $headers[] = $plugin_instance->getHeader();
      }
      $csv->insertOne($headers);
    }
    else {
      $csv = Writer::createFromPath($context['results']['file_path'], 'a');
    }

    // Add formatter for CSV formula injection prevention.
    $csv->addFormatter([new CsvEncoder(), 'formatRow']);

    // Load and process users.
    $user_storage = $entity_type_manager->getStorage('user');
    $users = $user_storage->loadMultiple($user_ids);

    foreach ($users as $user) {
      $row = [];
      foreach ($plugin_definitions as $plugin_definition) {
        /** @var \Drupal\social_user_export\Plugin\UserExportPluginInterface $plugin_instance */
        $plugin_instance = $user_export_plugin->createInstance($plugin_definition['id']);
        $row[] = $plugin_instance->getValue($user);
      }
      $csv->insertOne($row);
    }

    $context['message'] = t('Processing batch @batch for segment: @label', [
      '@batch' => $batch_number,
      '@label' => $segment_label,
    ]);
  }

  /**
   * Batch operation: Finalizes and saves the export file.
   *
   * @param string $filename
   *   The filename for the export.
   * @param string $segment_label
   *   The segment label for messaging.
   * @param array $context
   *   The batch context.
   */
  public static function finishBatch(string $filename, string $segment_label, array &$context): void {
    if (!isset($context['results']['file_path'])) {
      return;
    }

    /** @var \Drupal\user_segments\UserSegmentExportService $export_service */
    $export_service = \Drupal::service('user_segments.export');

    $temp_file_path = $context['results']['file_path'];
    $file = $export_service->saveToPrivateFileSystem($temp_file_path, $filename);

    if ($file !== NULL) {
      $context['results']['saved_file'] = $file;
      $context['results']['success'] = TRUE;
    }
    else {
      $context['results']['success'] = FALSE;
    }

    $context['message'] = t('Finalizing export for: @label', ['@label' => $segment_label]);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The batch results.
   * @param array $operations
   *   The remaining operations.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success && isset($results['saved_file'])) {
      $file = $results['saved_file'];
      $url = Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()));
      $link = Link::fromTextAndUrl(t('Download file'), $url);

      \Drupal::messenger()->addMessage(t('Export is complete. @link', [
        '@link' => $link->toString(),
      ]));
    }
    else {
      \Drupal::messenger()->addError(t('The export could not be completed.'));
    }
  }

}

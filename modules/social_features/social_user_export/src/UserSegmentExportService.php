<?php

declare(strict_types=1);

namespace Drupal\social_user_export;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\social_user_export\Plugin\UserExportPluginManager;
use Drupal\user_segments\Entity\UserSegment;
use Psr\Log\LoggerInterface;

/**
 * Provides user segment export functionality.
 *
 * Handles all business logic for creating, processing, and saving
 * user segment exports to CSV format.
 */
final class UserSegmentExportService {

  /**
   * The base directory for a user segment exporting.
   *
   * This is relative to the private:// stream wrapper.
   */
  public const string EXPORT_DIRECTORY = 'csv/user_segments_export';

  /**
   * The full URI for the user segment export directory.
   */
  public const string EXPORT_DIRECTORY_URI = 'private://csv/user_segments_export';

  /**
   * Constructs a UserSegmentExportService.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *   The file repository service.
   * @param \Drupal\social_user_export\Plugin\UserExportPluginManager $pluginManager
   *   The user export plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly FileRepositoryInterface $fileRepository,
    private readonly UserExportPluginManager $pluginManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Generates a unique filename for the export.
   *
   * Format: user_segment_{id}_{timestamp}_{hash}.csv.
   *
   * @param \Drupal\user_segments\Entity\UserSegment $segment
   *   The user segment entity.
   *
   * @return string
   *   The generated filename.
   */
  public function generateFilename(UserSegment $segment): string {
    return sprintf(
      'user_segment_%s_%s_%s.csv',
      $segment->id(),
      date('Y-m-d_H-i-s'),
      bin2hex(random_bytes(4))
    );
  }

  /**
   * Gets export plugin definitions sorted by weight.
   *
   * @return array
   *   Sorted array of plugin definitions.
   */
  public function getExportPluginDefinitions(): array {
    $definitions = $this->pluginManager->getDefinitions();
    usort($definitions, function ($a, $b) {
      return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
    });
    return $definitions;
  }

  /**
   * Creates a batch array for exporting a segment.
   *
   * @param \Drupal\user_segments\Entity\UserSegment $segment
   *   The user segment to export.
   * @param array $user_ids
   *   Array of user IDs to export.
   *
   * @return array
   *   Batch array suitable for batch_set().
   */
  public function createBatch(UserSegment $segment, array $user_ids): array {
    $filename = $this->generateFilename($segment);
    $plugin_definitions = $this->getExportPluginDefinitions();

    // Create batch operations (50 users per batch).
    $operations = [];
    $batch_size = 50;
    $user_id_batches = array_chunk($user_ids, $batch_size);

    foreach ($user_id_batches as $batch_number => $user_id_batch) {
      $operations[] = [
        '\Drupal\social_user_export\Controller\UserSegmentExportController::processBatch',
        [$batch_number + 1, $user_id_batch, $filename, $plugin_definitions, $segment->label()],
      ];
    }

    // Add final operation to save the file.
    $operations[] = [
      '\Drupal\social_user_export\Controller\UserSegmentExportController::finishBatch',
      [$filename, $segment->label()],
    ];

    return [
      'title' => t('Exporting user segment: @label', ['@label' => $segment->label()]),
      'operations' => $operations,
      'finished' => '\Drupal\social_user_export\Controller\UserSegmentExportController::batchFinished',
      'progressive' => TRUE,
    ];
  }

  /**
   * Saves a temporary file to the private file system.
   *
   * @param string $temp_file_path
   *   Path to a temporary file.
   * @param string $filename
   *   Destination filename.
   *
   * @return \Drupal\file\FileInterface|null
   *   The saved file entity, or NULL on failure.
   */
  public function saveToPrivateFileSystem(string $temp_file_path, string $filename): ?FileInterface {
    $data = @file_get_contents($temp_file_path);

    if ($data === FALSE) {
      return NULL;
    }

    // Ensure private directory exists.
    $directory = self::EXPORT_DIRECTORY_URI;
    $this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    // Save to the private file system.
    try {
      $file = $this->fileRepository->writeData(
        $data,
        $directory . '/' . $filename,
        FileExists::Replace
      );

      // Clean up a temporary file.
      @unlink($temp_file_path);

      return $file;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to save export file: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Validates if a segment can be exported.
   *
   * @param \Drupal\user_segments\Entity\UserSegment $segment
   *   The segment to validate.
   *
   * @return array
   *   Array with 'valid' (bool) and 'message' (string) keys.
   */
  public function validateSegment(UserSegment $segment): array {
    if (!$segment->get('status')->value) {
      return [
        'valid' => FALSE,
        'message' => t('Cannot export disabled user segment.'),
      ];
    }

    return ['valid' => TRUE, 'message' => ''];
  }

}

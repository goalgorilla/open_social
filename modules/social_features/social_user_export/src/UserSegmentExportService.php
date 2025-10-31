<?php

declare(strict_types=1);

namespace Drupal\social_user_export;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\social_user_export\Plugin\UserExportPluginManager;
use Drupal\user_segments\Entity\UserSegment;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;

/**
 * Provides user segment export functionality.
 *
 * Handles all business logic for creating, processing, and saving
 * user segment exports to CSV format.
 */
final class UserSegmentExportService {

  /**
   * The full URI for the user segment export directory.
   */
  public const string EXPORT_DIRECTORY_URI = 'private://csv';

  /**
   * Starting characters for spreadsheet formulas.
   */
  private const FORMULAS_START_CHARACTERS = ['=', '-', '+', '@', "\t", "\r"];

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly FileRepositoryInterface $fileRepository,
    private readonly UserExportPluginManager $pluginManager,
    private readonly LoggerInterface $logger,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns a unique file path for this export.
   *
   * The returned path is relative to getBaseOutputDirectory(). This allows it
   * to work on distributed systems where the temporary file path may change
   * in between batch ticks.
   *
   * To make sure the file can be downloaded, the path must be declared in the
   * download pattern of the social user export module.
   *
   * @see social_user_export_file_download()
   *
   * @return string
   *   The path to the file.
   */
  public function generateFilePath(): string {
    return 'export-segment-users-' . bin2hex(random_bytes(8)) . '.csv';
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
   * Gets user IDs from a user segment.
   *
   * @param \Drupal\user_segments\Entity\UserSegment $segment
   *   The user segment entity.
   *
   * @return array
   *   Array of user IDs in the segment.
   */
  public function getUserIdsInSegment(UserSegment $segment): array {
    $storage = $this->entityTypeManager->getStorage('user_segment');
    return $storage->getUserIdsInSegment($segment);
  }

  /**
   * Initiates export for a user segment.
   *
   * Handles getting user IDs, validation, and creating the batch process.
   *
   * @param \Drupal\user_segments\Entity\UserSegment $segment
   *   The user segment to export.
   *
   * @return array
   *   Array with keys:
   *   - 'batch': Batch array if export should proceed, NULL otherwise
   *   - 'message': Warning/error message if applicable
   *   - 'redirect_route': Route name to redirect to if export cannot proceed
   */
  public function initiateExport(UserSegment $segment): array {
    // Get user IDs from the segment using the storage API.
    $user_ids = $this->getUserIdsInSegment($segment);

    // Early return if no users found.
    if (empty($user_ids)) {
      return [
        'batch' => NULL,
        'message' => t('The selected user segment does not contain any users to export.'),
        'redirect_route' => 'entity.user_segment.canonical',
      ];
    }

    // Create batch using service.
    $batch = $this->createBatch($segment, $user_ids);

    return [
      'batch' => $batch,
      'message' => NULL,
      'redirect_route' => NULL,
    ];
  }

  /**
   * Write values to a CSV row.
   *
   * This also escapes strings starting with a formula character.
   *
   * @param array $row
   *   The row to inject the value into.
   * @param string $value
   *   The value to insert.
   */
  public function writeRow(array &$row, string $value): void {
    // The single quote ' is recommended to prefix formulas.
    if (\in_array(substr($value, 0, 1), self::FORMULAS_START_CHARACTERS, TRUE)) {
      $row[] = "'" . $value;
    }
    else {
      $row[] = $value;
    }
  }

  /**
   * Gets the base output directory for temporary files.
   *
   * @return string
   *   The base output directory path.
   */
  public function getBaseOutputDirectory(): string {
    return $this->fileSystem->getTempDirectory();
  }

  /**
   * Initializes CSV headers in batch context.
   *
   * Creates headers if they don't exist in the context. This follows the same
   * pattern as ExportUser for consistency, including storing the relative file
   * path for distributed systems compatibility.
   *
   * @param array $context_results
   *   Reference to the batch context results array (e.g., $context['results']).
   * @param string $relative_file_path
   *   Relative file path (filename only) for storing in context.
   *
   * @return \League\Csv\Writer
   *   The CSV writer instance.
   */
  public function initializeCsvHeaders(array &$context_results, string $relative_file_path): Writer {
    // Check if headers exist.
    if (empty($context_results['headers'])) {
      $headers = [];
      $plugin_definitions = $this->getExportPluginDefinitions();
      foreach ($plugin_definitions as $plugin_definition) {
        /** @var \Drupal\social_user_export\Plugin\UserExportPluginInterface $plugin_instance */
        $plugin_instance = $this->pluginManager->createInstance($plugin_definition['id']);
        $headers[] = $plugin_instance->getHeader();
      }
      $context_results['headers'] = $headers;
    }

    // Create the file if applicable.
    // Store only the relative path (filename) in results. On platforms such
    // as Pantheon, different batch ticks can happen on different webheads.
    // This can cause the file mount path to change, thus changing where on
    // disk the tmp folder is actually located.
    if (empty($context_results['file_path'])) {
      $context_results['file_path'] = $relative_file_path;
      $file_path = $this->getBaseOutputDirectory() . DIRECTORY_SEPARATOR . $relative_file_path;

      $csv = Writer::createFromPath($file_path, 'w');
      $csv->setDelimiter(',');
      $csv->setEnclosure('"');
      $csv->setEscape('\\');

      // Insert headers.
      $csv->insertOne($context_results['headers']);
    }
    else {
      // Reconstruct full path from relative path stored in context.
      $file_path = $this->getBaseOutputDirectory() . DIRECTORY_SEPARATOR . $context_results['file_path'];
      // Append to existing file.
      $csv = Writer::createFromPath($file_path, 'a');
    }

    // Add formatter for CSV formula injection prevention.
    $csv->addFormatter([new CsvEncoder(), 'formatRow']);

    return $csv;
  }

  /**
   * Processes a batch of users and writes them to CSV.
   *
   * This follows the same pattern as ExportUser for consistency, including
   * plugin configuration support.
   *
   * @param array $users
   *   Array of user entities to process.
   * @param \League\Csv\Writer $csv
   *   The CSV writer instance.
   */
  public function processUsersBatch(array $users, Writer $csv): void {
    $plugin_definitions = $this->getExportPluginDefinitions();

    // Process each user entity.
    foreach ($users as $entity_id => $user) {
      $row = [];
      foreach ($plugin_definitions as $plugin_definition) {
        // Get plugin configuration for this entity (follows ExportUser
        // pattern).
        $configuration = $this->getPluginConfiguration($plugin_definition['id'], $entity_id);
        /** @var \Drupal\social_user_export\Plugin\UserExportPluginInterface $plugin_instance */
        $plugin_instance = $this->pluginManager->createInstance($plugin_definition['id'], $configuration);
        // Convert value to string in case plugin returns TranslatableMarkup.
        $value = (string) $plugin_instance->getValue($user);
        $this->writeRow($row, $value);
      }
      $csv->insertOne($row);
    }
  }

  /**
   * Batch operation: Processes a batch of users.
   *
   * This is a static method for batch API compatibility. Batch operations run
   * in a different context where service instances cannot be serialized.
   *
   * @param int $batch_number
   *   The batch number.
   * @param array $user_ids
   *   Array of user IDs to process.
   * @param string $filename
   *   The filename for the export.
   * @param string $segment_label
   *   The segment label for messaging.
   * @param array $context
   *   The batch context.
   */
  public static function processBatch(int $batch_number, array $user_ids, string $filename, string $segment_label, array &$context): void {
    /** @var \Drupal\social_user_export\UserSegmentExportService $service */
    $service = \Drupal::service('user_segments.export');

    // Initialize segment label in results if needed.
    if (!isset($context['results']['segment_label'])) {
      $context['results']['segment_label'] = $segment_label;
    }

    // Initialize CSV headers and get writer (follows ExportUser pattern).
    // Pass relative file path (filename only) for distributed systems
    // compatibility.
    $csv = $service->initializeCsvHeaders($context['results'], $filename);

    // Load users.
    $entity_type_manager = \Drupal::entityTypeManager();
    $user_storage = $entity_type_manager->getStorage('user');
    $users = $user_storage->loadMultiple($user_ids);

    // Process users batch (follows ExportUser pattern).
    $service->processUsersBatch($users, $csv);

    $context['message'] = t('Processing batch @batch for segment: @label', [
      '@batch' => $batch_number,
      '@label' => $segment_label,
    ]);
  }

  /**
   * Batch operation: Finalizes and saves the export file.
   *
   * This is a static method for batch API compatibility. Batch operations run
   * in a different context where service instances cannot be serialized.
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

    /** @var \Drupal\social_user_export\UserSegmentExportService $service */
    $service = \Drupal::service('user_segments.export');

    // Reconstruct full temp file path from relative path stored in context.
    $relative_file_path = $context['results']['file_path'];
    $temp_file_path = $service->getBaseOutputDirectory() . DIRECTORY_SEPARATOR . $relative_file_path;
    $file = $service->saveToPrivateFileSystem($temp_file_path, $filename);

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
   * This is a static method for batch API compatibility. Batch callbacks run
   * in a different context where service instances cannot be serialized.
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
      /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
      $file_url_generator = \Drupal::service('file_url_generator');
      $file = $results['saved_file'];
      $url = Url::fromUri($file_url_generator->generateAbsoluteString($file->getFileUri()));
      $link = Link::fromTextAndUrl(t('Download file'), $url);

      \Drupal::messenger()->addMessage(t('Export is complete. @link', [
        '@link' => $link->toString(),
      ]));
    }
    else {
      \Drupal::messenger()->addError(t('The export could not be completed.'));
    }
  }

  /**
   * Gets export plugin's configuration.
   *
   * This follows the same pattern as ExportUser for consistency. Allows
   * plugins to receive entity-specific configuration if needed.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param int $entity_id
   *   The user entity ID.
   *
   * @return array
   *   An array of export plugin's configuration.
   */
  public function getPluginConfiguration(string $plugin_id, int $entity_id): array {
    // Currently no entity-specific configuration needed, but this follows
    // ExportUser's pattern for future extensibility.
    return [];
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
    $filename = $this->generateFilePath();
    $segment_label = $segment->label();

    // Create batch operations (50 users per batch).
    $operations = [];
    $batch_size = 50;
    $user_id_batches = array_chunk($user_ids, $batch_size);

    foreach ($user_id_batches as $batch_number => $user_id_batch) {
      $operations[] = [
        [self::class, 'processBatch'],
        [$batch_number + 1, $user_id_batch, $filename, $segment_label],
      ];
    }

    // Add final operation to save the file.
    $operations[] = [
      [self::class, 'finishBatch'],
      [$filename, $segment_label],
    ];

    return [
      'title' => t('Exporting user segment: @label', ['@label' => $segment_label]),
      'operations' => $operations,
      'finished' => [self::class, 'batchFinished'],
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

}

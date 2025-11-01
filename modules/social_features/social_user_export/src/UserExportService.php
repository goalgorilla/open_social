<?php

declare(strict_types=1);

namespace Drupal\social_user_export;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\file\FileRepository;
use Drupal\social_user_export\Plugin\UserExportPluginInterface;
use Drupal\social_user_export\Plugin\UserExportPluginManager;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;

/**
 * Service for exporting users to CSV.
 *
 * This service is VBO-agnostic and uses Drupal's Batch API directly.
 * It can be used from both VBO actions and controllers.
 */
final class UserExportService {

  use StringTranslationTrait;

  /**
   * Starting characters for spreadsheet formulas.
   */
  private const FORMULAS_START_CHARACTERS = ['=', '-', '+', '@', "\t", "\r"];

  /**
   * Batch size for processing users.
   */
  private const BATCH_SIZE = 50;

  /**
   * Constructs a UserExportService object.
   *
   * @param \Drupal\social_user_export\Plugin\UserExportPluginManager $userExportPlugin
   *   The user export plugin manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user account.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory for the export plugin access.
   * @param \Drupal\Core\File\FileUrlGenerator $fileUrlGenerator
   *   The file url generator service.
   * @param \Drupal\file\FileRepository $fileRepository
   *   The file repository service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected UserExportPluginManager $userExportPlugin,
    protected AccountProxyInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
    protected FileUrlGenerator $fileUrlGenerator,
    protected FileRepository $fileRepository,
    protected MessengerInterface $messenger,
    protected LoggerInterface $logger,
    protected ModuleHandlerInterface $moduleHandler,
    protected FileSystemInterface $fileSystem,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Export users to CSV using Drupal's Batch API.
   *
   * This service method provides a VBO-agnostic way to export users to CSV.
   * It uses Drupal's Batch API to handle large exports efficiently, processing
   * users in chunks to avoid timeouts. It is designed for HTTP request contexts
   * where you need to export large numbers of users (potentially 10,000+)
   * without loading them all into memory at once.
   *
   * When to use this method:
   * - HTTP requests from controllers (e.g., user segment export)
   * - Form submissions that need batch processing
   * - Any context where you have user IDs and need CSV export
   *
   * When NOT to use this method:
   * - VBO actions: Use `processVboBatch()` instead, which works within VBO's
   *   batch context structure
   * - Cron jobs or Drush commands: Batch API requires HTTP requests and
   *   session management. For CLI/non-interactive contexts, consider a
   *   different approach (e.g., queue workers or direct processing)
   * - REST/GraphQL API endpoints: Batch API requires browser redirects and
   *   session cookies, which don't work well with API clients
   * - When you need synchronous processing: This method always uses batch
   *   processing (asynchronous), so the export completes in subsequent
   *   HTTP requests
   *
   * Important: Do NOT load entities before calling this method.
   *
   * This method accepts user IDs (not entities) to avoid memory issues with
   * large exports. The service will load users in batches during processing.
   * Pass IDs directly from queries (e.g., `$storage->getUserIdsInSegment()`)
   * rather than loading entities first (e.g., `$storage->loadMultiple($ids)`).
   *
   * What the caller needs to do:
   * - Provide an array of user entity IDs (numeric integers). Do NOT load
   *   entities into memory, as this method is designed for large exports.
   *   The service will load users in batches during processing.
   * - If called from an HTTP request context, provide a redirect URL object
   *   (e.g., `Url::fromRoute()`) where the user should be sent after batch
   *   completion.
   * - No pre-configuration needed: The service handles plugin discovery,
   *   access checking, CSV file creation, and batch setup automatically.
   * - No post-processing needed: The service handles file saving to
   *   private://csv and displays download links via messenger service.
   *
   * Return value behavior:
   * - If `$redirectUrl` is provided: Returns a `RedirectResponse` that
   *   starts the batch processing. The batch will execute in subsequent
   *   HTTP requests, and the user will be redirected to the URL when
   *   complete.
   * - If `$redirectUrl` is NULL: Returns NULL. The batch is still set up
   *   and will be processed on the next HTTP request (via Drupal's batch
   *   page). Use this when you want to handle the redirect yourself or when
   *   the batch will be processed automatically.
   *
   * Architectural note:
   * This service exists to provide code reuse between different export
   * contexts (VBO actions, user segment exports, future export features).
   * The trade-off is that as a service, it has a broader scope than an action
   * plugin, which makes it more flexible but less self-documenting. Use
   * `processVboBatch()` for VBO-specific contexts, and this method for
   * general HTTP request contexts.
   *
   * @param array $user_ids
   *   Array of user entity IDs to export.
   * @param \Drupal\Core\Url|null $redirectUrl
   *   Optional redirect URL after batch completion. If provided, batch
   *   processing starts immediately and returns a RedirectResponse. If NULL,
   *   the batch is set up but processing starts on the next HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   Redirect response if $redirectUrl is provided (starts batch processing),
   *   or NULL if $redirectUrl is NULL (batch will be processed on next
   *   request).
   *
   * @see \Drupal\social_user_export\UserExportService::processVboBatch()
   *   For VBO-specific batch processing that maintains VBO context structure.
   *
   * @example
   *   // From a controller in an HTTP request:
   *   $user_ids = $storage->getUserIdsInSegment($segment);
   *   $redirectUrl = Url::fromRoute('entity.user_segment.collection');
   *   return $this->exportService->exportUsers($user_ids, $redirectUrl);
   */
  public function exportUsers(array $user_ids, ?Url $redirectUrl = NULL): ?RedirectResponse {
    if (empty($user_ids)) {
      $this->messenger->addWarning(t('No users to export.'));
      return NULL;
    }

    $pluginDefinitions = $this->getPluginDefinitions();

    // Generate headers.
    $headers = [];
    foreach ($pluginDefinitions as $plugin) {
      $instance = $this->userExportPlugin->createInstance($plugin['id']);
      assert($instance instanceof UserExportPluginInterface, 'Plugin manager should return UserExportPluginInterface instance.');
      $headers[] = $instance->getHeader();
    }

    // Generate a unique file path.
    $filePath = $this->generateFilePath();
    $baseDirectory = $this->getBaseOutputDirectory();
    $fullPath = $baseDirectory . DIRECTORY_SEPARATOR . $filePath;

    // Initialize CSV file with headers.
    $csv = $this->openCsvForWriting($fullPath, 'w');
    $csv->insertOne($headers);

    // Prepare batch operations.
    $total = count($user_ids);
    $batchOperations = [];
    $chunks = array_chunk($user_ids, self::BATCH_SIZE, TRUE);

    foreach ($chunks as $chunk) {
      $batchOperations[] = [
        [self::class, 'processBatch'],
        [
          $chunk,
          $pluginDefinitions,
          $filePath,
          $total,
        ],
      ];
    }

    // Set up batch.
    $batch = [
      'title' => $this->t('Exporting users'),
      'operations' => $batchOperations,
      'finished' => [self::class, 'batchFinished'],
      'progress_message' => $this->t('Processed @current out of @total.'),
      'file' => $this->moduleHandler->getModule('social_user_export')->getPath() . '/src/UserExportService.php',
    ];

    batch_set($batch);

    // Process batch if redirect URL is provided.
    // Convert URL to string only when needed for batch processing.
    if ($redirectUrl !== NULL) {
      return batch_process($redirectUrl->toString());
    }

    return NULL;
  }

  /**
   * Batch operation callback to process a chunk of users.
   *
   * @param array $userIds
   *   Array of user IDs to process in this batch.
   * @param array $pluginDefinitions
   *   Array of export plugin definitions.
   * @param string $filePath
   *   Relative file path (from temp directory).
   * @param int $total
   *   Total number of users to process.
   * @param array $context
   *   Batch context array.
   */
  public static function processBatch(array $userIds, array $pluginDefinitions, string $filePath, int $total, array &$context): void {
    $pluginManager = \Drupal::service('plugin.manager.user_export_plugin');
    $userStorage = \Drupal::entityTypeManager()->getStorage('user');

    // Initialize sandbox on the first run.
    if (!isset($context['sandbox']['file_path'])) {
      $context['sandbox']['file_path'] = $filePath;
      $context['sandbox']['processed'] = 0;
      $context['sandbox']['total'] = $total;
      // Store file path in results for batch finished callback.
      // This only needs to happen once during the initial setup since the file
      // path doesn't change.
      $context['results']['file_path'] = $filePath;
    }

    // Load users for this batch.
    $users = $userStorage->loadMultiple($userIds);

    // Get file path.
    // Note: We can't call $this->getBaseOutputDirectory() in a static method,
    // so we need to use the service directly. The method exists for
    // extensibility in non-static contexts (VBO batch processing via
    // processVboBatch).
    $fileSystem = \Drupal::service('file_system');
    $baseDirectory = $fileSystem->getTempDirectory();
    $fullPath = $baseDirectory . DIRECTORY_SEPARATOR . $context['sandbox']['file_path'];

    // Open CSV file in appended mode.
    // This is the copy of $csv = $this->openCsvForWriting($fullPath, 'a');.
    $csv = Writer::createFromPath($fullPath, 'a');
    $csv->setDelimiter(',');
    $csv->setEnclosure('"');
    $csv->setEscape('\\');
    $csv->addFormatter([new CsvEncoder(), 'formatRow']);

    // Process each user in this batch.
    foreach ($users as $user) {
      $row = [];
      foreach ($pluginDefinitions as $plugin) {
        $configuration = [];
        $instance = $pluginManager->createInstance(
          $plugin['id'],
          $configuration
        );
        assert($instance instanceof UserExportPluginInterface, 'Plugin manager should return UserExportPluginInterface instance.');
        $value = $instance->getValue($user);
        // Convert to string in case it's a TranslatableMarkup object.
        $value = (string) $value;
        // Note: Can't call instance method in static context, so we inline the
        // logic here. This duplicates the logic from writeRow() but is
        // necessary for static methods.
        if (\in_array(substr($value, 0, 1), self::FORMULAS_START_CHARACTERS, TRUE)) {
          $row[] = "'" . $value;
        }
        else {
          $row[] = $value;
        }
      }
      $csv->insertOne($row);
      $context['sandbox']['processed']++;
    }

    // Update progress.
    $context['finished'] = $context['sandbox']['processed'] >= $context['sandbox']['total'] ? 1 : ($context['sandbox']['processed'] / $context['sandbox']['total']);
    $context['message'] = \Drupal::translation()->translate('Processing user @current of @total', [
      '@current' => $context['sandbox']['processed'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   Batch results array.
   * @param array $operations
   *   Array of operations that were executed.
   *
   * @todo PROD-34345: Refactor to remove messenger messages from this method.
   *   Adding messages can be disruptive in certain circumstances (e.g., API
   *   requests, cron, background processes). Instead, this method should return
   *   the file URL/information, and let the calling code decide whether and how
   *   to display messages to users. This will make the service more flexible
   *   and reusable across different contexts.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();
    $logger = \Drupal::service('logger.factory')->get('social_user_export');

    if ($success) {
      // The file path is stored in results during initial setup.
      if (!isset($results['file_path'])) {
        $messenger->addError(\Drupal::translation()->translate('Could not locate export file.'));
        $logger->error('Could not locate export file after batch completion.');
        return;
      }

      $filePath = $results['file_path'];

      // Read a file and save to private://csv.
      // Note: We can't call $this->getBaseOutputDirectory() in a static method,
      // so we need to use the service directly.
      $fileSystem = \Drupal::service('file_system');
      $baseDirectory = $fileSystem->getTempDirectory();
      $fullPath = $baseDirectory . DIRECTORY_SEPARATOR . $filePath;
      $data = @file_get_contents($fullPath);

      if (!is_string($data)) {
        $messenger->addError(\Drupal::translation()->translate('Could not read export file.'));
        $logger->error('Could not read export file: %path', ['%path' => $fullPath]);
        return;
      }

      $name = basename($filePath);
      $path = 'private://csv';

      $fileSystem = \Drupal::service('file_system');
      if ($fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        $fileRepository = \Drupal::service('file.repository');
        $fileRepository->writeData($data, $path . '/' . $name);

        $fileUrlGenerator = \Drupal::service('file_url_generator');
        $url = Url::fromUri($fileUrlGenerator->generateAbsoluteString($path . '/' . $name));
        $link = Link::fromTextAndUrl(\Drupal::translation()->translate('Download file'), $url);

        $messenger->addMessage(\Drupal::translation()->translate('Export is complete. @link', [
          '@link' => $link->toString(),
        ]));
      }
      else {
        $messenger->addError(\Drupal::translation()->translate('Could not save the export file.'));
        $logger->error('Could not save the export file: %name', ['%name' => $name]);
      }
    }
    else {
      // An error occurred.
      $errorOperation = reset($operations);
      $messenger->addError(\Drupal::translation()->translate('An error occurred while processing the export.'));
      $logger->error('Export batch failed with operation: @operation', [
        '@operation' => $errorOperation[0] ?? 'unknown',
      ]);
    }
  }

  /**
   * Check the access of export plugins based on config and permission.
   *
   * @param array $definitions
   *   The plugin definitions.
   *
   * @return array
   *   Returns only the plugins the user has access to.
   */
  protected function pluginAccess(array $definitions): array {
    // When the user has access to administer users, we know they may export all
    // the available data.
    if ($this->currentUser->hasPermission('administer users')) {
      return $definitions;
    }

    // Now we go through all the definitions and check if they should be removed
    // or not based upon the config set by the site manager.
    $config = $this->configFactory->get('social_user_export.settings');
    $allowedPlugins = $config->get('plugins') ?? [];
    foreach ($definitions as $key => $definition) {
      if (!array_key_exists($definition['id'], $allowedPlugins) || empty($allowedPlugins[$definition['id']])) {
        unset($definitions[$key]);
      }
    }

    return $definitions;
  }

  /**
   * Order by weight.
   *
   * @param array $a
   *   First parameter.
   * @param array $b
   *   Second parameter.
   *
   * @return int
   *   The weight to be used for the usort function.
   */
  protected function sortDefinitions(array $a, array $b): int {
    if (isset($a['weight'], $b['weight'])) {
      return $a['weight'] < $b['weight'] ? -1 : 1;
    }
    return 0;
  }

  /**
   * Returns the directory that forms the base for this exports file output.
   *
   * This method wraps FileSystemInterface::getTempDirectory() to give
   * inheriting classes the ability to use a different file system than the
   * temporary file system.
   * This was previously possible but was changed in #3075818.
   *
   * @return string
   *   The path to the Drupal directory that should be used for this export.
   */
  protected function getBaseOutputDirectory(): string {
    return $this->fileSystem->getTempDirectory();
  }

  /**
   * Opens a CSV file for writing with standard configuration.
   *
   * @param string $filePath
   *   Full path to the CSV file.
   * @param string $mode
   *   File mode ('w' for write, 'a' for append).
   *
   * @return \League\Csv\Writer
   *   Configured CSV writer instance.
   */
  private function openCsvForWriting(string $filePath, string $mode = 'w'): Writer {
    $csv = Writer::createFromPath($filePath, $mode);
    $csv->setDelimiter(',');
    $csv->setEnclosure('"');
    $csv->setEscape('\\');
    $csv->addFormatter([new CsvEncoder(), 'formatRow']);
    return $csv;
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
  protected function writeRow(array &$row, string $value): void {
    // The single quote ' is recommended to prefix formulas.
    if (\in_array(substr($value, 0, 1), self::FORMULAS_START_CHARACTERS, TRUE)) {
      $row[] = "'" . $value;
    }
    else {
      $row[] = $value;
    }
  }

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
  protected function generateFilePath(): string {
    return 'export-users-' . bin2hex(random_bytes(8)) . '.csv';
  }

  /**
   * Process a batch of users within VBO context.
   *
   * This method is designed to be called from VBO actions that work within
   * VBO's batch system. It handles CSV writing and file management within
   * the VBO context structure.
   *
   * @param array $entities
   *   Array of user entities to process.
   * @param array $pluginDefinitions
   *   Array of export plugin definitions.
   * @param array &$context
   *   VBO context array with sandbox structure.
   * @param callable|null $getPluginConfiguration
   *   Optional callback to get plugin configuration for a specific entity.
   *   Signature: function(string $plugin_id, int $entity_id): array.
   *
   *   This callable should not be relied upon but is kept in place for
   *   backwards compatibility of classes depending on ExportUser. Our goal is
   *   to remove this in the future.
   *
   * @return array
   *   Empty array (VBO expects this).
   */
  public function processVboBatch(array $entities, array $pluginDefinitions, array &$context, ?callable $getPluginConfiguration = NULL): array {
    // Initialize headers if needed.
    if (empty($context['sandbox']['results']['headers'])) {
      $headers = [];
      foreach ($pluginDefinitions as $plugin) {
        $instance = $this->userExportPlugin->createInstance($plugin['id']);
        assert($instance instanceof UserExportPluginInterface, 'Plugin manager should return UserExportPluginInterface instance.');
        $headers[] = $instance->getHeader();
      }
      $context['sandbox']['results']['headers'] = $headers;
    }

    // Initialize file if needed.
    if (empty($context['sandbox']['results']['file_path'])) {
      $context['sandbox']['results']['file_path'] = $this->generateFilePath();
      $baseDirectory = $this->getBaseOutputDirectory();
      $filePath = $baseDirectory . DIRECTORY_SEPARATOR . $context['sandbox']['results']['file_path'];

      $csv = $this->openCsvForWriting($filePath, 'w');
      $csv->insertOne($context['sandbox']['results']['headers']);
    }
    else {
      $baseDirectory = $this->getBaseOutputDirectory();
      $filePath = $baseDirectory . DIRECTORY_SEPARATOR . $context['sandbox']['results']['file_path'];
      $csv = $this->openCsvForWriting($filePath, 'a');
    }

    // Process each entity.
    foreach ($entities as $entity_id => $entity) {
      $row = [];
      foreach ($pluginDefinitions as $plugin) {
        $configuration = $getPluginConfiguration !== NULL
          ? $getPluginConfiguration($plugin['id'], $entity_id)
          : [];
        $instance = $this->userExportPlugin->createInstance(
          $plugin['id'],
          $configuration
        );
        assert($instance instanceof UserExportPluginInterface, 'Plugin manager should return UserExportPluginInterface instance.');
        $value = $instance->getValue($entity);
        // Convert to string in case it's a TranslatableMarkup object.
        $value = (string) $value;
        // Escape formulas using the writeRow helper method.
        $this->writeRow($row, $value);
      }
      $csv->insertOne($row);
    }

    // Check if this is the last batch and finalize.
    if (($context['sandbox']['current_batch'] * $context['sandbox']['batch_size']) >= $context['sandbox']['total']) {
      $this->finalizeExport($filePath, $context['sandbox']['results']['file_path']);
    }

    return [];
  }

  /**
   * Finalize export by moving file to private://csv and displaying a message.
   *
   * @param string $tempFilePath
   *   Full path to a temporary file.
   * @param string $relativePath
   *   Relative file path (filename only).
   *
   * @todo PROD-34345: Refactor to remove messenger messages from this method.
   *   Adding messages can be disruptive in certain circumstances (e.g., API
   *   requests, cron, background processes). Instead, this method should return
   *   the file URL/information, and let the calling code decide whether and how
   *   to display messages to users. This will make the service more flexible
   *   and reusable across different contexts.
   */
  protected function finalizeExport(string $tempFilePath, string $relativePath): void {
    $data = @file_get_contents($tempFilePath);
    if (!is_string($data)) {
      $this->messenger->addError($this->t('Could not read export file.'));
      $this->logger->error('Could not read export file: %path', ['%path' => $tempFilePath]);
      return;
    }

    $name = basename($relativePath);
    $path = 'private://csv';

    if ($this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->fileRepository->writeData($data, $path . '/' . $name);
      $url = Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($path . '/' . $name));
      $link = Link::fromTextAndUrl($this->t('Download file'), $url);

      $this->messenger->addMessage($this->t('Export is complete. @link', [
        '@link' => $link->toString(),
      ]));
    }
    else {
      $this->messenger->addError($this->t('Could not save the export file.'));
      $this->logger->error('Could not save the export file: %name', ['%name' => $name]);
    }
  }

  /**
   * Get plugin definitions filtered by access.
   *
   * @return array
   *   Array of plugin definitions the current user has access to.
   */
  public function getPluginDefinitions(): array {
    $definitions = $this->userExportPlugin->getDefinitions();
    $pluginDefinitions = $this->pluginAccess($definitions);
    usort($pluginDefinitions, [$this, 'sortDefinitions']);
    return $pluginDefinitions;
  }

}

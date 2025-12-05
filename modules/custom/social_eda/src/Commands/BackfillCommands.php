<?php

declare(strict_types=1);

namespace Drupal\social_eda\Commands;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_eda\Plugin\BackfillHandlerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for EDA backfill operations.
 */
class BackfillCommands extends DrushCommands {

  /**
   * Constructs a BackfillCommands object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $backfillHandlerManager
   *   The backfill handler manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected PluginManagerInterface $backfillHandlerManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * List available backfill handler plugins.
   *
   * @command social-eda:backfill-list
   * @aliases seda:bf-list
   * @usage social-eda:backfill-list
   *   List all available backfill handler plugins.
   */
  public function listPlugins(): void {
    $definitions = $this->backfillHandlerManager->getDefinitions();

    if (empty($definitions)) {
      $this->output()->writeln('No backfill handler plugins found.');
      return;
    }

    $this->output()->writeln('Available backfill handler plugins:');
    $this->output()->writeln('');

    foreach ($definitions as $id => $definition) {
      $label = $definition['label'] ?? $id;
      $entity_type = $definition['entity_type'] ?? 'unknown';
      $bundle = $definition['bundle'] ?? 'unknown';

      $this->output()->writeln(sprintf(
        '  <info>%s</info> - %s (%s:%s)',
        $id,
        $label,
        $entity_type,
        $bundle
      ));
    }
  }

  /**
   * Queue entities for EDA backfill processing.
   *
   * @param string $type
   *   The backfill handler plugin ID, or 'all' to process all plugins.
   * @param array $options
   *   Command options.
   *
   * @command social-eda:backfill
   * @aliases seda:bf
   * @option from Start date for backfill (format: Y-m-d, e.g., 2023-01-01)
   * @option to End date for backfill (format: Y-m-d, e.g., 2023-12-31)
   * @option dry-run Show what would be queued without actually queuing
   * @option batch-size Number of jobs to queue at once (default: 100)
   * @usage social-eda:backfill topic
   *   Queue all topics for backfill.
   * @usage social-eda:backfill all --from=2023-01-01 --to=2023-12-31
   *   Queue all entities created in 2023 for backfill.
   * @usage social-eda:backfill post --dry-run
   *   Show how many posts would be queued without queuing them.
   */
  public function backfill(
    string $type,
    array $options = [
      'from' => NULL,
      'to' => NULL,
      'dry-run' => FALSE,
      'batch-size' => 100,
    ],
  ): void {
    // Merge with defaults to handle partial option arrays.
    $options = array_merge([
      'from' => NULL,
      'to' => NULL,
      'dry-run' => FALSE,
      'batch-size' => 100,
    ], $options);

    $definitions = $this->backfillHandlerManager->getDefinitions();

    if (empty($definitions)) {
      $this->output()->writeln('<error>No backfill handler plugins found.</error>');
      return;
    }

    // Parse date options.
    try {
      $from = $this->parseDate($options['from']);
      $to = $this->parseDate($options['to'], TRUE);
    }
    catch (\InvalidArgumentException $e) {
      // Invalid date format - abort command.
      return;
    }

    // Determine which plugins to process.
    if ($type === 'all') {
      $plugin_ids = array_keys($definitions);
    }
    else {
      if (!isset($definitions[$type])) {
        $this->output()->writeln(sprintf('<error>Unknown backfill handler plugin: %s</error>', $type));
        $this->output()->writeln('Use <info>social-eda:backfill-list</info> to see available plugins.');
        return;
      }
      $plugin_ids = [$type];
    }

    $dry_run = (bool) $options['dry-run'];
    $batch_size = max(1, (int) $options['batch-size']);

    if ($dry_run) {
      $this->output()->writeln('<comment>DRY RUN - No jobs will be queued.</comment>');
      $this->output()->writeln('');
    }

    // Load the queue.
    $queue = NULL;
    if (!$dry_run) {
      $queue_storage = $this->entityTypeManager->getStorage('advancedqueue_queue');
      $queue_entity = $queue_storage->load('social_eda_backfill');
      if (!$queue_entity instanceof QueueInterface) {
        $this->output()->writeln('<error>Queue "social_eda_backfill" not found. Please ensure social_eda module is properly installed.</error>');
        return;
      }
      $queue = $queue_entity;
    }

    $total_queued = 0;

    foreach ($plugin_ids as $plugin_id) {
      $definition = $definitions[$plugin_id];
      $label = $definition['label'] ?? $plugin_id;
      $entity_type = $definition['entity_type'] ?? NULL;

      if ($entity_type === NULL) {
        $this->output()->writeln(sprintf('<error>Plugin "%s" is missing required "entity_type" configuration. Skipping.</error>', $plugin_id));
        continue;
      }

      $this->output()->writeln(sprintf('Processing <info>%s</info> (%s)...', $label, $plugin_id));

      // Create plugin instance to get entity IDs.
      $handler = $this->backfillHandlerManager->createInstance($plugin_id);
      if (!$handler instanceof BackfillHandlerInterface) {
        $this->output()->writeln(sprintf('<error>Handler for plugin "%s" must implement BackfillHandlerInterface.</error>', $plugin_id));
        continue;
      }

      $entity_ids = $handler->getEntityIds($from, $to);
      $count = count($entity_ids);

      if ($count === 0) {
        $this->output()->writeln('  No entities found.');
        continue;
      }

      $this->output()->writeln(sprintf('  Found %d entities.', $count));

      if ($dry_run) {
        $total_queued += $count;
        continue;
      }

      // Queue jobs in batches.
      $batches = array_chunk($entity_ids, $batch_size);
      $queued = 0;

      foreach ($batches as $batch) {
        $jobs = [];
        foreach ($batch as $entity_id) {
          $job = Job::create('social_eda_backfill', [
            'plugin_id' => $plugin_id,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
          ]);
          $jobs[] = $job;
        }

        // Queue is guaranteed to be non-null here due to early return above.
        $queue->enqueueJobs($jobs);
        $queued += count($jobs);

        $this->output()->writeln(sprintf('  Queued %d/%d jobs...', $queued, $count));
      }

      $total_queued += $queued;
    }

    $this->output()->writeln('');
    if ($dry_run) {
      $this->output()->writeln(sprintf('<comment>Would have queued %d jobs total.</comment>', $total_queued));
    }
    else {
      $this->output()->writeln(sprintf('<info>Queued %d jobs total.</info>', $total_queued));
      $this->output()->writeln('');
      $this->output()->writeln('Run <info>drush advancedqueue:queue:process social_eda_backfill</info> to process the queue.');
    }
  }

  /**
   * Parse a date string into a Unix timestamp.
   *
   * @param string|null $date
   *   The date string (Y-m-d format).
   * @param bool $end_of_day
   *   If TRUE, return end of day (23:59:59), otherwise start of day (00:00:00).
   *
   * @return int|null
   *   Unix timestamp, or NULL if no date provided.
   *
   * @throws \InvalidArgumentException
   *   When the date format is invalid.
   */
  protected function parseDate(?string $date, bool $end_of_day = FALSE): ?int {
    if ($date === NULL || $date === '') {
      return NULL;
    }

    $datetime = \DateTime::createFromFormat('Y-m-d', $date, new \DateTimeZone('UTC'));
    if ($datetime === FALSE || $datetime->format('Y-m-d') !== $date) {
      $message = sprintf('Invalid date format: %s. Use Y-m-d format (e.g., 2023-01-01).', $date);
      $this->output()->writeln(sprintf('<error>%s</error>', $message));
      throw new \InvalidArgumentException($message);
    }

    if ($end_of_day) {
      $datetime->setTime(23, 59, 59);
    }
    else {
      $datetime->setTime(0, 0, 0);
    }

    return $datetime->getTimestamp();
  }

}

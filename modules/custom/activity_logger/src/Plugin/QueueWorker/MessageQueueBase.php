<?php

namespace Drupal\activity_logger\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Provides base functionality for the ReportWorkers.
 */
abstract class MessageQueueBase extends QueueWorkerBase {

  /**
   * The queue.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * MessageQueueBase constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueFactory $queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->queue = $queue;
  }

  /**
   * Simple reporter log and display information about the queue.
   *
   * @param string $queue_name
   *   The queue name.
   * @param object $data
   *   The $data which should be stored in the queue item.
   */
  protected function createQueueItem($queue_name, $data) {
    $queue = $this->queue->get($queue_name);
    $queue->createItem($data);
  }

}

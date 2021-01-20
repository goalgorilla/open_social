<?php

namespace Drupal\activity_logger\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Provides base functionality for the ReportWorkers.
 */
abstract class MessageQueueBase extends QueueWorkerBase {

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   *
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

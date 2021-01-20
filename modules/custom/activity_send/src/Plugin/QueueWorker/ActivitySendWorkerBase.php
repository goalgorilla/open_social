<?php

namespace Drupal\activity_send\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Provides base functionality for the ActivitySendWorkers.
 */
abstract class ActivitySendWorkerBase extends QueueWorkerBase {

  /**
   * Create queue item.
   *
   * @param string $queue_name
   *   The queue name.
   * @param object $data
   *   The $data which should be stored in the queue item.
   */
  protected function createQueueItem($queue_name, $data) {
    /** @phpstan-ignore-next-line: cannot inject the QueueFactory, since the plugins below already override the QueueWorkerBase constructor. */
    $queue = \Drupal::queue($queue_name);
    $queue->createItem($data);
  }

}

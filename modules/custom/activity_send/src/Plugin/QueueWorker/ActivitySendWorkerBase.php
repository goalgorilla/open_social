<?php

/**
 * @file
 * Contains \Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase.
 */

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
    $queue = \Drupal::queue($queue_name);
    $queue->createItem($data);
  }
}

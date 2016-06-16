<?php

/**
 * @file
 * Contains \Drupal\activity_logger\Plugin\QueueWorker\MessageQueueBase.
 */

namespace Drupal\activity_logger\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the ReportWorkers.
 */
abstract class MessageQueueBase extends QueueWorkerBase {
  /**
   * Simple reporter log and display information about the queue.
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

<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\QueueWorker\ActivityWorkerBase.
 */

namespace Drupal\activity_creator\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides base functionality for the ReportWorkers.
 */
abstract class ActivityWorkerBase extends QueueWorkerBase {

  use StringTranslationTrait;

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

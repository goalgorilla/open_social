<?php

/**
 * @file
 * Contains \Drupal\activity_logger\Plugin\QueueWorker\MessageQueueCreator.
 */

namespace Drupal\activity_logger\Plugin\QueueWorker;

use Drupal\node\Entity\Node;


/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "activity_logger_message",
 *   title = @Translation("Process activity_logger_message queue."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for creating message items from the queue
 */
class MessageQueueCreator extends MessageQueueBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    // First make sure it's an actual entity.
    if ($entity = Node::load($data['entity_id'])) {
      // Check if it's created more than 20 seconds ago.
      $timestamp = $entity->getCreatedTime();
      // Current time.
      $now = time();
      $diff = $now - $timestamp;

      // Items must be at least 5 seconds old.
      if ($diff <= 5) {
        // Wait for 100 milliseconds.
        // We don't want to flood the DB with unprocessable queue items.
        usleep(100000);
        $queue = \Drupal::queue('activity_logger_message');
        $queue->createItem($data);
      }
      else {
        $activity_logger_factory = \Drupal::service('plugin.manager.activity_action.processor');
        // Trigger the create action for enttites.
        $create_action = $activity_logger_factory->createInstance('create_entitiy_action');
        $create_action->createMessage($entity);
      }
    }
  }

}

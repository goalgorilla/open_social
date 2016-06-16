<?php

/**
 * @file
 * Contains \Drupal\activity_logger\Plugin\QueueWorker\MessageQueueCreator
 */

namespace Drupal\activity_logger\Plugin\QueueWorker;

use Drupal\message\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;


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
    $entity = $data['entity'];

    // Get the services we need here.
    $activityLoggerFactory = \Drupal::service('activity_logger.activity_factory');
    $contextGetter = \Drupal::service('activity_logger.context_getter');

    // Get all messages that are responsible for creating items.
    $message_types = $activityLoggerFactory->getMessageTypes('create', $entity);
    // Loop through those message types and create messages.
    foreach ($message_types as $message_type => $message_values) {
      // Create the ones applicable for this bundle.
      if ($message_values['bundle'] === $entity->bundle()) {

        // Determine destinations.
        $destinations = [];
        if (!empty($message_values['destinations']) && is_array($message_values['destinations'])) {
          foreach ($message_values['destinations'] as $destination) {
            $destinations[] = array('value' => $destination);
          }
        }

        // Get context
        $context = $contextGetter->getContext($entity);

        // Set the values.
        $new_message['type'] = $message_type;
        $new_message['uid'] = $entity->getOwner()->id();
        $new_message['field_message_context'] = $context;
        $new_message['field_message_destination'] = $destinations;
        $new_message['field_message_related_object'] = [
          'target_type' => $entity->getEntityTypeId(),
          'target_id' => $entity->id(),
        ];

        // Create the message
        $message = Message::create($new_message);
        $message->setArguments(array());
        $message->save();
      }
    }
  }
}

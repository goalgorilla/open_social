<?php

namespace Drupal\activity_logger\Service;

use Drupal\Core\Entity\Entity;
use Drupal\message\Entity\Message;

/**
 * Class ActivityLoggerFactory
 * @package Drupal\activity_logger\Service
 * Service that determines which actions need to be performed.
 */
class ActivityLoggerFactory {

  /**
   * @param Entity $entity
   * @param string $action
   * @return void
   */
  public function createMessages($entity, $action = 'create') {
    // Context service.
    $contextGetter = \Drupal::service('activity_logger.context_getter');

    // Get all messages that are responsible for creating items.
    $message_types = $this->getMessageTypes('create', $entity);
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
        $message->save();
      }
    }
  }

  /**
   * @param $action
   * @param $entity
   * @return array
   */
  public function getMessageTypes($action, $entity) {
    // Init.
    $messagetypes = array();

    // Get the context of the entity
    $context = $this->getEntityContext($entity);

    // We need the entitytype manager.
    $entity_type_manager = \Drupal::service('entity_type.manager');
    // Message type storage.
    $message_storage = $entity_type_manager->getStorage('message_type');

    // Check all enabled messages.
    foreach($message_storage->loadByProperties(array('status' => '1')) as $key => $messagetype) {
      // Messagetype must be a part of the context the items is in.
      if ($messagetype->getThirdPartySetting('activity_logger', 'activity_context', NULL) === $context) {
        // @TODO: Make this configurable.
        $messagetype_action = explode('_', $key)[0];
        $bundletype = explode('_', $key)[1];
        // Determine the action types to return.
        if ($action === $messagetype_action && $entity->bundle() === $bundletype) {
          $messagetypes[$key] = array(
            'messagetype' => $messagetype,
            'bundle' => $bundletype,
            'destinations' => $messagetype->getThirdPartySetting('activity_logger', 'activity_destinations', NULL),
          );
        }
      }
    }
    // Return the message types that belong to the requested action.
    return $messagetypes;
  }

  public function getEntityContext($entity) {

    // Fetch entity context.
    $contextGetter = \Drupal::service('activity_logger.context_getter');
    $context = $contextGetter->getContext($entity);
    // Return the entity context.
    return $context;

  }
}

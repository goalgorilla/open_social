<?php

namespace Drupal\activity_logger\Service;

use Drupal\Core\Entity\Entity;
use Drupal\message\Entity\Message;

/**
 * Class ActivityLoggerFactory.
 *
 * @package Drupal\activity_logger\Service
 * Service that determines which actions need to be performed.
 */
class ActivityLoggerFactory {

  /**
   * Create message entities.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *    Entity object to create a message for.
   * @param string $action
   *    Action string. Defaults to 'create'.
   */
  public function createMessages(Entity $entity, $action) {
    // Get all messages that are responsible for creating items.
    $message_types = $this->getMessageTypes($action, $entity);
    // Loop through those message types and create messages.
    foreach ($message_types as $message_type => $message_values) {
      // Create the ones applicable for this bundle.
      // Determine destinations.
      $destinations = [];
      if (!empty($message_values['destinations']) && is_array($message_values['destinations'])) {
        foreach ($message_values['destinations'] as $destination) {
          $destinations[] = array('value' => $destination);
        }
      }

      $mt_context = $message_values['context'];

      // Set the values.
      $new_message['template'] = $message_type;
      $new_message['created'] = $entity->getCreatedTime();
      $new_message['uid'] = $entity->getOwner()->id();

      $additional_fields = [
        ['name' => 'field_message_context', 'type' => 'list_string'],
        ['name' => 'field_message_destination', 'type' => 'list_string'],
        [
          'name' => 'field_message_related_object',
          'type' => 'dynamic_entity_reference',
        ],
      ];
      $this->createFieldInstances($message_type, $additional_fields);

      $new_message['field_message_context'] = $mt_context;
      $new_message['field_message_destination'] = $destinations;

      $new_message['field_message_related_object'] = [
        'target_type' => $entity->getEntityTypeId(),
        'target_id' => $entity->id(),
      ];

      // Create the message.
      $message = Message::create($new_message);

      $message->save();

    }
  }

  /**
   * Get message templates for action and entity.
   *
   * @param string $action
   *    Action string, e.g. 'create'.
   * @param \Drupal\Core\Entity\Entity $entity
   *    Entity object.
   *
   * @return array
   *    Array of message types.
   */
  public function getMessageTypes($action, Entity $entity) {
    // Init.
    $messagetypes = array();

    // We need the entitytype manager.
    $entity_type_manager = \Drupal::service('entity_type.manager');
    // Message type storage.
    $message_storage = $entity_type_manager->getStorage('message_template');

    // Check all enabled messages.
    foreach ($message_storage->loadByProperties(array('status' => '1')) as $key => $messagetype) {
      $mt_entity_bundles = $messagetype->getThirdPartySetting('activity_logger', 'activity_bundle_entities', NULL);
      $mt_action = $messagetype->getThirdPartySetting('activity_logger', 'activity_action', NULL);
      $mt_context = $messagetype->getThirdPartySetting('activity_logger', 'activity_context', NULL);
      $mt_destinations = $messagetype->getThirdPartySetting('activity_logger', 'activity_destinations', NULL);
      $mt_entity_condition = $messagetype->getThirdPartySetting('activity_logger', 'activity_entity_condition', NULL);

      if (!empty($mt_entity_condition)) {
        $entity_condition_factory = \Drupal::service('plugin.manager.activity_entity_condition.processor');
        $entity_condition_plugin = $entity_condition_factory->createInstance($mt_entity_condition);
        $entity_condition = $entity_condition_plugin->isValidEntityCondition($entity);
      }
      else {
        $entity_condition = TRUE;
      }

      $activity_context_factory = \Drupal::service('plugin.manager.activity_context.processor');
      $context_plugin = $activity_context_factory->createInstance($mt_context);

      $entity_bundle_name = $entity->getEntityTypeId() . '-' . $entity->bundle();
      if (in_array($entity_bundle_name, $mt_entity_bundles)
        && $context_plugin->isValidEntity($entity)
        && $entity_condition
        && $action === $mt_action
      ) {
        $messagetypes[$key] = array(
          'messagetype' => $messagetype,
          'bundle' => $entity_bundle_name,
          'destinations' => $mt_destinations,
          'context' => $mt_context,
        );
      }
    }
    // Return the message types that belong to the requested action.
    return $messagetypes;
  }

  /**
   * Create field instances.
   *
   * @param string $message_type
   *   The typeof message.
   * @param array $fields
   *   The data to insert in the field instances.
   */
  protected function createFieldInstances($message_type, array $fields) {
    foreach ($fields as $field) {
      $id = 'message.' . $message_type . '.' . $field['name'];
      $config_storage = \Drupal::entityTypeManager()
        ->getStorage('field_config');
      // Create field instances if they do not exits.
      if ($config_storage->load($id) === NULL) {
        $field_instace = [
          'langcode' => 'en',
          'status' => TRUE,
          'config' => [
            'field.storage.message.' . $field['name'],
            'message.template.' . $message_type,
          ],
          'module' => ['options'],
          'id' => $id,
          'field_name' => $field['name'],
          'entity_type' => 'message',
          'bundle' => $message_type,
          'label' => '',
          'description' => '',
          'reqiured' => FALSE,
          'translatable' => FALSE,
          'default_value' => [],
          'default_value_callback' => '',
          'field_type' => $field['type'],
        ];

        if ($field['type'] === 'list_string') {
          $field_instance['module'] = ['options'];
          $field_instance['settings'] = [];
        }
        elseif ($field['type'] === 'dynamic_entity_reference') {
          $field_instance['module'] = ['dynamic_entity_reference'];
          $field_instance['settings'] = [];
        }
        $config_storage->create($field_instace)->save();
      }
    }
  }

}

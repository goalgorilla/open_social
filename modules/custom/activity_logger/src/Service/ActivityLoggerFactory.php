<?php
/**
 * @file
 * Activity Logger Factory to create message entities.
 */

namespace Drupal\activity_logger\Service;

use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
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
  public function createMessages(\Drupal\Core\Entity\Entity $entity, $action) {
    // Get all messages that are responsible for creating items.
    $message_types = $this->getMessageTypes($action, $entity);
    // Loop through those message types and create messages.
    foreach ($message_types as $message_type => $message_values) {
      // Create the ones applicable for this bundle.
      // Determine destinations.
      $destinations = [];
      $group = [];
      $groupcontent = [];
      if (!empty($message_values['destinations']) && is_array($message_values['destinations'])) {
        foreach ($message_values['destinations'] as $destination) {
          $destinations[] = array('value' => $destination);
        }
      }

      $mt_context = $message_values['context'];

      // Set the values.
      $new_message['type'] = $message_type;
      $new_message['uid'] = $entity->getOwner()->id();
      $new_message['field_message_context'] = $mt_context;
      $new_message['field_message_destination'] = $destinations;
      $new_message['field_message_related_object'] = [
        'target_type' => $entity->getEntityTypeId(),
        'target_id' => $entity->id(),
      ];

      // Create the message.
      $message = Message::create($new_message);

      // Try to get the group.
      $groupcontent = GroupContent::loadByEntity($entity);
      if (!empty($groupcontent)) {
        $groupcontent = reset($groupcontent);
        $group = $groupcontent->getGroup();
      }
      // Or special handling for post entities.
      if ($entity->getEntityTypeId() === 'post') {
        if ($entity->getEntityTypeId() === 'post' && !empty($entity->get('field_recipient_group')
            ->getValue())
        ) {
          $group = Group::load($group_id = $entity->field_recipient_group->target_id);
        }
      }
      // If it's a group.. add it in the arguments.
      if ($group instanceof Group) {
        $gurl = Url::fromRoute('entity.group.canonical', array(
          'group' => $group->id(),
          array()
        ));
        $message->setArguments(array(
          'groups' => [
            'gtitle' => $group->label(),
            'gurl' => $gurl->toString(),
          ],
        ));
      }

      $message->save();

    }
  }


  /**
   * Get message types for action and entity.
   *
   * @param string $action
   *    Action string, e.g. 'create'.
   * @param \Drupal\Core\Entity\Entity $entity
   *    Entity object.
   *
   * @return array
   *    Array of message types.
   */
  public function getMessageTypes($action, \Drupal\Core\Entity\Entity $entity) {
    // Init.
    $messagetypes = array();

    // We need the entitytype manager.
    $entity_type_manager = \Drupal::service('entity_type.manager');
    // Message type storage.
    $message_storage = $entity_type_manager->getStorage('message_type');

    // Check all enabled messages.
    foreach ($message_storage->loadByProperties(array('status' => '1')) as $key => $messagetype) {
      $mt_entity_bundle = $messagetype->getThirdPartySetting('activity_logger', 'activity_bundle_entity', NULL);
      $mt_action = $messagetype->getThirdPartySetting('activity_logger', 'activity_action', NULL);
      $mt_context = $messagetype->getThirdPartySetting('activity_logger', 'activity_context', NULL);
      $mt_destinations = $messagetype->getThirdPartySetting('activity_logger', 'activity_destinations', NULL);

      $activity_context_factory = \Drupal::service('plugin.manager.activity_context.processor');
      $context_plugin = $activity_context_factory->createInstance($mt_context);

      $entity_bundle_name = $entity->getEntityTypeId() . '.' .$entity->bundle();
      if ($entity_bundle_name === $mt_entity_bundle
      && $context_plugin->isValidEntity($entity)
      && $action === $mt_action) {
        $messagetypes[$key] = array(
          'messagetype' => $messagetype,
          'bundle' => $mt_entity_bundle,
          'destinations' => $mt_destinations,
          'context' => $mt_context,
        );
      }
    }
    // Return the message types that belong to the requested action.
    return $messagetypes;
  }

}

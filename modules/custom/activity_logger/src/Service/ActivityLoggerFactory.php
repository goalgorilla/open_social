<?php

namespace Drupal\activity_logger\Service;

use Drupal\activity_creator\Plugin\ActivityContextManager;
use Drupal\activity_creator\Plugin\ActivityEntityConditionManager;
use Drupal\activity_logger\Entity\NotificationConfigEntityInterface;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\message\Entity\Message;
use Drupal\user\EntityOwnerInterface;

/**
 * Class ActivityLoggerFactory.
 *
 * @package Drupal\activity_logger\Service
 * Service that determines which actions need to be performed.
 */
class ActivityLoggerFactory {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The condition manager.
   *
   * @var \Drupal\activity_creator\Plugin\ActivityEntityConditionManager
   */
  protected $activityEntityConditionManager;

  /**
   * The context manager.
   *
   * @var \Drupal\activity_creator\Plugin\ActivityContextManager
   */
  protected $activityContextManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ActivityLoggerFactory constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\activity_creator\Plugin\ActivityEntityConditionManager $activityEntityConditionManager
   *   The condition manager.
   * @param \Drupal\activity_creator\Plugin\ActivityContextManager $activityContextManager
   *   The context manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ActivityEntityConditionManager $activityEntityConditionManager,
    ActivityContextManager $activityContextManager,
    ModuleHandlerInterface $moduleHandler) {
    $this->entityTypeManager = $entityTypeManager;
    $this->activityEntityConditionManager = $activityEntityConditionManager;
    $this->activityContextManager = $activityContextManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Create message entities.
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Entity object to create a message for.
   * @param string $action
   *   Action string. Defaults to 'create'.
   */
  public function createMessages(EntityBase $entity, $action): void {
    // Get all messages that are responsible for creating items.
    $message_types = $this->getMessageTypes($action, $entity);
    // Loop through those message types and create messages.
    foreach ($message_types as $message_type => $message_values) {
      // Create the ones applicable for this bundle.
      // Determine destinations.
      $destinations = [];
      if (!empty($message_values['destinations']) && is_array($message_values['destinations'])) {
        foreach ($message_values['destinations'] as $destination) {
          $destinations[] = ['value' => $destination];
        }
      }

      $mt_context = $message_values['context'];

      // Set the values.
      $new_message['template'] = $message_type;

      // Get the owner or default to anonymous.
      if ($entity instanceof EntityOwnerInterface && $entity->getOwner() !== NULL) {
        $new_message['uid'] = (string) $entity->getOwner()->id();
      }
      else {
        $new_message['uid'] = '0';
      }

      // This is performed in an assert so that the code doesn't run in
      // production. The check loads field configs which can be an expensive
      // operation in the hot-path that is activity handling.
      assert($this->debugMessageTemplateIsCompatible($message_type), "Message template '$message_type' is not compatible with the activity system. This can be because the template is missing required fields or the fields are wrongly configured. See " . __CLASS__ . "::debugMessageTemplateIsCompatible for the requirements.");

      $new_message['field_message_context'] = $mt_context;
      $new_message['field_message_destination'] = $destinations;

      if ($entity instanceof NotificationConfigEntityInterface) {
        $new_message['field_message_related_object'] = [
          'target_type' => $entity->getEntityTypeId(),
          'target_id' => $entity->getUniqueId(),
        ];
        $new_message['created'] = $entity->getCreatedTime();
      }
      else {
        $new_message['field_message_related_object'] = [
          'target_type' => $entity->getEntityTypeId(),
          'target_id' => $entity->id(),
        ];
        // The flagging entity does not implement getCreatedTime().
        if ($entity->getEntityTypeId() === 'flagging') {
          $new_message['created'] = $entity->get('created')->value;
        }
        else {
          $new_message['created'] = $entity->getCreatedTime();
        }
      }

      // Create the message only if it doesn't exist.
      if (!$this->checkIfMessageExist($new_message['template'], $new_message['field_message_context'], $new_message['field_message_destination'], $new_message['field_message_related_object'], $new_message['uid'])) {
        $message = Message::create($new_message);
        $message->save();
      }

    }
  }

  /**
   * Get message templates for action and entity.
   *
   * @param string $action
   *   Action string, e.g. 'create'.
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Entity object.
   *
   * @return array
   *   Array of message types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getMessageTypes($action, EntityBase $entity) {
    // Init.
    $messagetypes = [];

    // Message type storage.
    $message_storage = $this->entityTypeManager->getStorage('message_template');

    // Check all enabled messages.
    foreach ($message_storage->loadByProperties(['status' => '1']) as $key => $messagetype) {
      $mt_entity_bundles = $messagetype->getThirdPartySetting('activity_logger', 'activity_bundle_entities', NULL);
      $mt_action = $messagetype->getThirdPartySetting('activity_logger', 'activity_action', NULL);
      $mt_context = $messagetype->getThirdPartySetting('activity_logger', 'activity_context', NULL);
      $mt_destinations = $messagetype->getThirdPartySetting('activity_logger', 'activity_destinations', NULL);
      $mt_entity_condition = $messagetype->getThirdPartySetting('activity_logger', 'activity_entity_condition', NULL);

      if (!empty($mt_entity_condition)
        && $this->activityEntityConditionManager->hasDefinition($mt_entity_condition)
      ) {
        $entity_condition_plugin = $this->activityEntityConditionManager->createInstance($mt_entity_condition);
        $entity_condition = $entity_condition_plugin->isValidEntityCondition($entity);
      }
      else {
        $entity_condition = TRUE;
      }

      if ($this->activityContextManager->hasDefinition($mt_context)) {
        $context_plugin = $this->activityContextManager->createInstance($mt_context);

        $entity_bundle_name = $entity->getEntityTypeId() . '-' . $entity->bundle();
        if (in_array($entity_bundle_name, $mt_entity_bundles)
          && $context_plugin->isValidEntity($entity)
          && $entity_condition
          && $action === $mt_action
        ) {
          $messagetypes[$key] = [
            'messagetype' => $messagetype,
            'bundle' => $entity_bundle_name,
            'destinations' => $mt_destinations,
            'context' => $mt_context,
          ];
        }
      }
    }
    // Return the message types that belong to the requested action.
    return $messagetypes;
  }

  /**
   * Checks that a message template is compatible with the activity system.
   *
   * To support activities, various fields need to exist on a message template,
   * with the right configuration. Below are the most important configurations
   * for a field that are checked, though these are not complete examples of
   * a field config.
   *
   * ```yaml
   * message.<message_type>.field_message_context:
   *   langcode: en
   *   status: TRUE
   *   id: message.<message_type>.field_message_context
   *   field_type: list_string
   *   module: ['options']
   *   field_name: field_message_context
   *   required: FALSE
   *   translatable: FALSE
   *
   * message.<message_type>.field_message_destination:
   *   langcode: en
   *   status: TRUE
   *   id: message.<message_type>.field_message_destination
   *   field_type: list_string
   *   module: ['options']
   *   field_name: field_message_destination
   *   required: FALSE
   *   translatable: FALSE
   *
   * message.<message_type>.field_message_related_object:
   *   langcode: en
   *   status: TRUE
   *   id: message.<message_type>.field_message_related_object
   *   field_type: dynamic_entity_reference
   *   module: ['dynamic_entity_reference']
   *   field_name: field_message_related_object
   *   required: FALSE
   *   translatable: FALSE
   * ```
   *
   * @param string $message_type
   *   The ID of the message type to check for.
   *
   * @return bool
   *   Whether the message type is compatible.
   */
  private function debugMessageTemplateIsCompatible(string $message_type) : bool {
    $config_storage = $this->entityTypeManager
      ->getStorage('field_config');

    $field_message_context = $config_storage->load("message.$message_type.field_message_context");
    if (!$field_message_context instanceof FieldConfigInterface) {
      return FALSE;
    }
    if (
      !$field_message_context->status() ||
      $field_message_context->getType() !== 'list_string' ||
      $field_message_context->isRequired() ||
      $field_message_context->isTranslatable()
    ) {
      return FALSE;
    }

    $field_message_destination = $config_storage->load("message.$message_type.field_message_destination");
    if (!$field_message_destination instanceof FieldConfigInterface) {
      return FALSE;
    }
    if (
      !$field_message_destination->status() ||
      $field_message_destination->getType() !== 'list_string' ||
      $field_message_destination->isRequired() ||
      $field_message_destination->isTranslatable()
    ) {
      return FALSE;
    }

    $field_message_related_object = $config_storage->load("message.$message_type.field_message_related_object");
    if (!$field_message_related_object instanceof FieldConfigInterface) {
      return FALSE;
    }
    if (
      !$field_message_related_object->status() ||
      $field_message_related_object->getType() !== 'dynamic_entity_reference' ||
      $field_message_related_object->isRequired() ||
      $field_message_related_object->isTranslatable()
    ) {
      return FALSE;
    }

    return TRUE;
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
    @trigger_error(__METHOD__ . ' is deprecated in social:11.7.0 and is removed from social:12.0.0. Create the fields using `config/install` instead. See https://www.drupal.org/node/3232246', E_USER_DEPRECATED);
    foreach ($fields as $field) {
      $id = 'message.' . $message_type . '.' . $field['name'];
      $config_storage = $this->entityTypeManager
        ->getStorage('field_config');
      // Create field instances if they do not exists.
      if ($config_storage->load($id) === NULL) {
        $field_instance = [
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
          'required' => FALSE,
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
        $config_storage->create($field_instance)->save();
      }
    }
  }

  /**
   * Checks if a message already exists.
   *
   * @param string $message_type
   *   The message type.
   * @param string $context
   *   The context of the message.
   * @param array $destination
   *   The array of destinations to check for, include delta as well.
   * @param array $related_object
   *   The related object, include target_type and target_id in array.
   * @param string $uid
   *   The uid of the message.
   *
   * @return bool
   *   Returns true if the message exists.
   */
  public function checkIfMessageExist(string $message_type, string $context, array $destination, array $related_object, string $uid): bool {
    $exists = FALSE;
    $query = $this->entityTypeManager->getStorage('message')->getQuery();
    $query->condition('template', $message_type);
    $query->condition('field_message_related_object.target_id', $related_object['target_id']);
    $query->condition('field_message_related_object.target_type', $related_object['target_type']);
    $query->condition('field_message_context', $context);
    $query->condition('uid', $uid);
    foreach ($destination as $delta => $dest_value) {
      $query->condition('field_message_destination.' . $delta . '.value', $dest_value['value']);
    }
    $query->accessCheck(FALSE);

    // Fix duplicates for create_bundle_group && moved_content_between_groups
    // create_bundle_group is run on cron, it could be there is already a
    // message for moving content between groups. So we need to make sure we
    // check if either create_bundle_group or move_content is already there
    // before we add another message that content is created in a group.
    $types = [
      'moved_content_between_groups',
      'create_topic_group',
      'create_event_group',
    ];

    if (in_array($message_type, $types, TRUE)) {
      $query = $this->entityTypeManager->getStorage('message')->getQuery();
      $query->condition('template', $types, 'IN');
      $query->condition('field_message_related_object.target_id', $related_object['target_id']);
      $query->condition('field_message_related_object.target_type', $related_object['target_type']);
      $query->condition('field_message_context', $context);
      $query->condition('uid', $uid);
      $query->accessCheck(FALSE);
    }

    $ids = $query->execute();

    $allowed_duplicates = ['moved_content_between_groups'];
    $this->moduleHandler->alter('activity_allowed_duplicates', $allowed_duplicates);

    if (!empty($ids) && !in_array($message_type, $allowed_duplicates)) {
      $exists = TRUE;
    }
    return $exists;
  }

}

<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\GroupMuteNotify;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'OwnerActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "owner_activity_context",
 *   label = @Translation("Owner activity context"),
 * )
 */
class OwnerActivityContext extends ActivityContextBase {

  /**
   * The group mute notifications.
   *
   * @var \Drupal\social_group\GroupMuteNotify
   */
  protected $groupMuteNotify;

  /**
   * Constructs a MentionActivityContext object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\Sql\QueryFactory $entity_query
   *   The query factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\activity_creator\ActivityFactory $activity_factory
   *   The activity factory service.
   * @param \Drupal\social_group\GroupMuteNotify $group_mute_notify
   *   The group mute notifications.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
    GroupMuteNotify $group_mute_notify
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->groupMuteNotify = $group_mute_notify;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query.sql'),
      $container->get('entity_type.manager'),
      $container->get('activity_creator.activity_factory'),
      $container->get('social_group.group_mute_notify')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = $this->activityFactory->getActivityRelatedEntity($data);
      $allowed_entity_types = ['node', 'post', 'comment'];
      if (in_array($related_entity['target_type'], $allowed_entity_types)) {
        $recipients += $this->getRecipientOwnerFromEntity($related_entity, $data);
      }
    }

    // Remove the actor (user performing action) from recipients list.
    if (!empty($data['actor'])) {
      $key = array_search($data['actor'], array_column($recipients, 'target_id'), FALSE);
      if ($key !== FALSE) {
        unset($recipients[$key]);
      }
    }

    return $recipients;
  }

  /**
   * Returns owner recipient from entity.
   *
   * @param array $related_entity
   *   The related entity.
   * @param array $data
   *   The data.
   *
   * @return array
   *   An associative array of recipients, containing the following key-value
   *   pairs:
   *   - target_type: The entity type ID.
   *   - target_id: The entity ID.
   */
  public function getRecipientOwnerFromEntity(array $related_entity, array $data) {
    $recipients = [];

    $entity_storage = $this->entityTypeManager->getStorage($related_entity['target_type']);
    $entity = $entity_storage->load($related_entity['target_id']);

    // It could happen that a notification has been queued but the content
    // has since been deleted. In that case we can find no additional
    // recipients.
    if (!$entity) {
      return $recipients;
    }

    // Don't return recipients if user comments on own content.
    $original_related_object = $data['related_object'][0];
    if (isset($original_related_object['target_type']) && $original_related_object['target_type'] === 'comment') {
      $storage = $this->entityTypeManager->getStorage($original_related_object['target_type']);
      $original_related_entity = $storage->load($original_related_object['target_id']);

      if (!empty($original_related_entity) && $original_related_entity->getOwnerId() === $entity->getOwnerId()) {
        return $recipients;
      }
    }

    if ($entity instanceof EntityOwnerInterface) {
      /** @var \Drupal\Core\Session\AccountInterface $account */
      $account = $entity->getOwner();
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->groupMuteNotify->getGroupByContent($entity);
      // Check if we have $group set which means that this content was
      // posted in a group.
      if (!empty($group) && $group instanceof GroupInterface) {
        // Skip the notification for users which have muted the group
        // notification in which this content was posted.
        if ($this->groupMuteNotify->groupNotifyIsMuted($group, $account)) {
          return $recipients;
        }
      }
    }

    if (isset($original_related_object['target_type']) && $original_related_object['target_type'] === 'event_enrollment') {
      $storage = $this->entityTypeManager->getStorage($original_related_object['target_type']);
      $original_related_entity = $storage->load($original_related_object['target_id']);

      // In the case where a user is added by an event manager we'll need to
      // check on the enrollment status. If the user is not really enrolled we
      // should skip sending the notification.
      if ($original_related_entity->get('field_enrollment_status')->value === '0') {
        return $recipients;
      }

      if (!empty($original_related_entity) && $original_related_entity->getAccount() !== NULL) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $original_related_entity->getAccount(),
        ];

        return $recipients;
      }
    }

    $recipients[] = [
      'target_type' => 'user',
      'target_id' => $entity->getOwnerId(),
    ];

    return $recipients;
  }

}

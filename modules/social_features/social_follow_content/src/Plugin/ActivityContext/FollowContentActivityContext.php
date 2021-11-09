<?php

namespace Drupal\social_follow_content\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_comment\Entity\Comment;
use Drupal\social_group\GroupMuteNotify;
use Drupal\social_node\Entity\Node;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'FollowContentActivityContext' activity context plugin.
 *
 * @ActivityContext(
 *   id = "follow_content_activity_context",
 *   label = @Translation("Following content activity context"),
 * )
 */
class FollowContentActivityContext extends ActivityContextBase {

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

      if ($related_entity['target_type'] == 'node') {
        $recipients += $this->getRecipientsWhoFollowContent($related_entity, $data);
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
  public function getRecipientsWhoFollowContent(array $related_entity, array $data) {
    $recipients = [];

    $storage = $this->entityTypeManager->getStorage('flagging');
    $flaggings = $storage->loadByProperties([
      'flag_id' => 'follow_content',
      'entity_type' => $related_entity['target_type'],
      'entity_id' => $related_entity['target_id'],
    ]);

    // We don't send notifications to users about their own comments.
    $original_related_object = $data['related_object'][0];
    $storage = $this->entityTypeManager->getStorage($original_related_object['target_type']);
    $original_related_entity = $storage->load($original_related_object['target_id']);

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->groupMuteNotify->getGroupByContent($original_related_entity);

    foreach ($flaggings as $flagging) {
      /** @var \Drupal\flag\FlaggingInterface $flagging */
      $recipient = $flagging->getOwner();

      // It could happen that a notification has been queued but the content or
      // account has since been deleted. In that case we can find no recipient.
      if (!$recipient instanceof UserInterface) {
        continue;
      }

      // The owner of a node automatically follows their own content.
      // Because of this, we do not want to send a follow notification.
      if ($original_related_entity instanceof Comment) {
        // We need to compare the owner ID of the original node to the one
        // being the current recipient.
        $original_node = $original_related_entity->getCommentedEntity();
        if ($original_node instanceof Node && $recipient->id() === $original_node->getOwnerId()) {
          continue;
        }
      }

      // Check if we have $group set which means that this content was
      // posted in a group.
      if (!empty($group) && $group instanceof GroupInterface) {
        // Skip the notification for users which have muted the group
        // notification in which this content was posted.
        if ($this->groupMuteNotify->groupNotifyIsMuted($group, $recipient)) {
          continue;
        }
      }

      if ($recipient->id() !== $original_related_entity->getOwnerId() && $original_related_entity->access('view', $recipient)) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $recipient->id(),
        ];
      }
    }
    return $recipients;
  }

}

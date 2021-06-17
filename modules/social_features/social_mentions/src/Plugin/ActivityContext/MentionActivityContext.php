<?php

namespace Drupal\social_mentions\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\GroupMuteNotify;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MentionActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "mention_activity_context",
 *   label = @Translation("Mention activity context"),
 * )
 */
class MentionActivityContext extends ActivityContextBase {

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
    $mentions = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_object = $data['related_object'][0];
      $mentions_storage = $this->entityTypeManager->getStorage('mentions');

      if ($related_object['target_type'] === 'mentions') {
        $mentions[] = $mentions_storage->load($related_object['target_id']);
      }
      else {
        $entity_storage = $this->entityTypeManager->getStorage($related_object['target_type']);
        $entity = $entity_storage->load($related_object['target_id']);
        $mentions = $this->getMentionsFromRelatedEntity($entity);
      }

      if (!empty($mentions)) {
        /** @var \Drupal\mentions\MentionsInterface $mention */
        foreach ($mentions as $mention) {
          if (isset($mention->uid)) {
            $uid = $mention->getMentionedUserId();

            // Don't send notifications to myself.
            if ($uid === $data['actor']) {
              continue;
            }

            $entity_storage = $this->entityTypeManager->getStorage($mention->getMentionedEntityTypeId());
            $mentioned_entity = $entity_storage->load($mention->getMentionedEntityId());

            /** @var \Drupal\user\UserInterface $account */
            $account = $mention->uid->entity;

            if ($mentioned_entity->access('view', $account)) {
              /** @var \Drupal\group\Entity\GroupInterface $group */
              $group = $this->groupMuteNotify->getGroupByContent($mentioned_entity);
              // Check if we have $group set which means that this content was
              // posted in a group.
              if (!empty($group) && $group instanceof GroupInterface) {
                // Skip the notification for users which have muted the group
                // notification in which this content was posted.
                if ($this->groupMuteNotify->groupNotifyIsMuted($group, $account)) {
                  continue;
                }
              }

              $recipients[] = [
                'target_type' => 'user',
                'target_id' => $uid,
              ];
            }
          }
        }
      }

    }

    return $recipients;
  }

  /**
   * Check for valid entity.
   */
  public function isValidEntity(EntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'mentions') {
      return TRUE;
    }

    // Special cases for comments and posts.
    $allowed_content_types = [
      'comment',
    ];

    if (in_array($entity->getEntityTypeId(), $allowed_content_types)) {
      $mentions = $this->getMentionsFromRelatedEntity($entity);

      if (!empty($mentions)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the mentions from the related entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The mentions.
   */
  public function getMentionsFromRelatedEntity(EntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'comment') {
      if ($entity->hasParentComment()) {
        $entity = $entity->getParentComment();
      }
    }

    // Mention entity can't be loaded at time of new post or comment creation.
    return $this->entityTypeManager->getStorage('mentions')->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
  }

}

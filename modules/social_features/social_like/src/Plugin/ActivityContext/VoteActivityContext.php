<?php

namespace Drupal\social_like\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\GroupMuteNotify;
use Drupal\user\EntityOwnerInterface;
use Drupal\votingapi\VoteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'VoteActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "vote_activity_context",
 *   label = @Translation("Vote activity context"),
 * )
 */
class VoteActivityContext extends ActivityContextBase {

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
      $related_object = $data['related_object'][0];

      if ($related_object['target_type'] === 'vote') {
        $vote_storage = $this->entityTypeManager->getStorage('vote');
        $vote = $vote_storage->load($related_object['target_id']);

        if ($vote instanceof VoteInterface) {
          $entity_storage = $this->entityTypeManager->getStorage($vote->getVotedEntityType());

          /** @var \Drupal\Core\Entity\EntityInterface $entity */
          $entity = $entity_storage->load($vote->getVotedEntityId());

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

          $uid = $entity->getOwnerId();

          // Don't send notifications to myself.
          if ($uid !== $data['actor']) {
            $recipients[] = [
              'target_type' => 'user',
              'target_id' => $uid,
            ];
          }
        }
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    return $entity->getEntityTypeId() === 'vote';
  }

}

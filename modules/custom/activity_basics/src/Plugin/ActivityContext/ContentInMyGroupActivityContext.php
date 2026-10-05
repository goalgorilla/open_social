<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_group\CrossPostingService;
use Drupal\social_group\GroupMuteNotify;
use Drupal\social_post\Entity\PostInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ContentInMyGroupActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "content_in_my_group_activity_context",
 *   label = @Translation("Content in my group activity context"),
 * )
 */
class ContentInMyGroupActivityContext extends ActivityContextBase {

  /**
   * How many member ids are looked up in a single membership query.
   */
  private const MEMBER_QUERY_CHUNK_SIZE = 1000;

  /**
   * The group mute notifications.
   *
   * @var \Drupal\social_group\GroupMuteNotify
   */
  protected $groupMuteNotify;

  /**
   * The cross-posting service.
   *
   * @var \Drupal\social_group\CrossPostingService
   */
  protected $crossPostingService;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a GroupRelationshipInMyGroupActivityContext object.
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
   * @param \Drupal\social_group\CrossPostingService $cross_posting_service
   *   The cross-posting service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
    GroupMuteNotify $group_mute_notify,
    CrossPostingService $cross_posting_service,
    Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->groupMuteNotify = $group_mute_notify;
    $this->crossPostingService = $cross_posting_service;
    $this->database = $database;
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
      $container->get('social_group.group_mute_notify'),
      $container->get('social_group.cross_posting'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, int $last_id, int $limit): array {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $referenced_entity = $this->activityFactory->getActivityRelatedEntity($data);
      $owner_id = '';
      // The id of the group relationship this activity is built for. It stays
      // NULL for posts, which are related to a group by a field instead of by
      // a relationship and are therefore never cross-posted.
      $relationship_id = NULL;

      if (isset($referenced_entity['target_type']) && $referenced_entity['target_type'] === 'post') {
        try {
          $entity = $this->entityTypeManager->getStorage('post')
            ->load($referenced_entity['target_id']);
        }
        catch (PluginNotFoundException $exception) {
          return $recipients;
        }

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if ($entity === NULL) {
          return $recipients;
        }

        $gid = $entity->get('field_recipient_group')->getValue();
        $owner_id = $entity->getOwnerId();
      }
      else {
        $group_content = $this->entityTypeManager->getStorage('group_content')
          ->load($referenced_entity['target_id']);

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if ($group_content === NULL) {
          return $recipients;
        }

        $entity = $group_content->getEntity();

        if ($entity instanceof EntityOwnerInterface) {
          $owner_id = $entity->getOwnerId();

          if (
            $entity instanceof EntityPublishedInterface &&
            !$entity->isPublished()
          ) {
            return $recipients;
          }
        }

        $gid = $group_content->get('gid')->getValue();
        $relationship_id = (int) $group_content->id();
      }

      if ($gid && isset($gid[0]['target_id'])) {
        $target_id = $gid[0]['target_id'];

        $recipients[] = [
          'target_type' => 'group',
          'target_id' => $target_id,
        ];

        $group = $this->entityTypeManager->getStorage('group')
          ->load($target_id);

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if (!$group instanceof GroupInterface) {
          return $recipients;
        }

        $memberships = $group->getMembers();

        // Index the accounts of this group by user id. There can be incidences
        // where even if the user was deleted its membership data was left in
        // the table group_relationship_field_data, so it is necessary to check
        // if the user actually exists in system.
        $member_accounts = [];
        /** @var \Drupal\group\GroupMembership $membership */
        foreach ($memberships as $membership) {
          $group_user = $membership->getUser();
          if ($group_user !== NULL) {
            $member_accounts[(int) $group_user->id()] = $group_user;
          }
        }

        // Content cross-posted to several groups produces one message, and
        // therefore one activity, per group. Readers who belong to more than
        // one of those groups are kept as a recipient of a single activity so
        // that every channel delivers the notification once.
        $covered_uids = [];
        if (
          $relationship_id !== NULL &&
          $entity instanceof NodeInterface &&
          isset($data['message_template'])
        ) {
          $covered_uids = $this->getUidsCoveredByLowerGroup(
            (string) $data['message_template'],
            $entity,
            $relationship_id,
            $member_accounts
          );
        }

        foreach ($member_accounts as $uid => $group_user) {
          // Check if this is not the created user and didn't mute the group
          // notifications.
          if (
            $owner_id != $uid &&
            // An array lookup, so tested before the two queries below.
            !isset($covered_uids[$uid]) &&
            !$this->groupMuteNotify->groupNotifyIsMuted($group, $group_user) &&
            $entity->access('view', $group_user)
          ) {
            $recipients[] = [
              'target_type' => 'user',
              'target_id' => $group_user->id(),
            ];
          }
        }
      }
    }

    return $recipients;
  }

  /**
   * Finds the readers a lower cross-posted group already notifies.
   *
   * Cross-posted content produces one activity per group. Each reader is
   * attached to a single one of them — the lowest group id they are an
   * unmuted member of — so that every channel delivers once.
   *
   * Only relationships already carrying a message of this template count, and
   * each lower group's mute is honoured. Both rules exist for the same
   * reason: suppressing a reader on behalf of a notification that is never
   * sent would leave them with nothing.
   *
   * Two limits, neither enforced here. The ordering holds only while every
   * message exists before the first activity is queued, which
   * `activity_create_direct: FALSE` guarantees. And membership and mute are
   * read at queue time, so a reader who joins or unmutes the lowest group
   * between two queue items is notified by neither; closing that needs
   * persisted state, which the acceptance criteria rule out.
   *
   * @param string $message_template
   *   The message template the activity is created for.
   * @param \Drupal\node\NodeInterface $node
   *   The cross-posted node.
   * @param int $relationship_id
   *   The id of the group relationship the activity is created for.
   * @param \Drupal\Core\Session\AccountInterface[] $member_accounts
   *   The accounts of the members of the current group, keyed by user id.
   *
   * @return int[]
   *   The ids of the members an earlier cross-posted group already notifies,
   *   keyed by user id. Empty whenever the content is not cross-posted or the
   *   current relationship is the earliest one that notifies.
   */
  private function getUidsCoveredByLowerGroup(string $message_template, NodeInterface $node, int $relationship_id, array $member_accounts): array {
    if ($member_accounts === []) {
      return [];
    }

    // With the feature off only one relationship ever carries a message, so
    // the lookups below are a no-op. This sits on the notification queue's
    // hot path, so they are skipped rather than run to completion.
    if (!$this->crossPostingService->notifiesAllGroups()) {
      return [];
    }

    // The access check of the other cross-posting lookups would be evaluated
    // against the account processing the queue, which is cron or the anonymous
    // user, so the unfiltered lookup is used here on purpose.
    $gids_per_relationship = $this->crossPostingService
      ->getGroupIdsByRelationshipForNodeWithoutAccessCheck($node);

    if (count($gids_per_relationship) < 2) {
      return [];
    }

    $message_ids = $this->entityTypeManager->getStorage('message')->getQuery()
      ->accessCheck(FALSE)
      ->condition('template', $message_template)
      ->condition('field_message_related_object.target_type', 'group_content')
      ->condition('field_message_related_object.target_id', array_keys($gids_per_relationship), 'IN')
      ->execute();

    if (!$message_ids) {
      return [];
    }

    $notifying = [];
    /** @var \Drupal\message\MessageInterface $message */
    foreach ($this->entityTypeManager->getStorage('message')->loadMultiple($message_ids) as $message) {
      foreach ($message->get('field_message_related_object')->getValue() as $value) {
        $notifying[(int) $value['target_id']] = TRUE;
      }
    }

    $notifying_gids = array_intersect_key($gids_per_relationship, $notifying);
    // The lookup reads the ids as strings, hence the explicit numeric sort.
    asort($notifying_gids, SORT_NUMERIC);

    // Without a message of its own the current relationship has no place in
    // the ordering, and nothing can be said about who it covers.
    if (!isset($notifying_gids[$relationship_id])) {
      return [];
    }

    $position = array_search($relationship_id, array_keys($notifying_gids), TRUE);
    if ($position === 0) {
      return [];
    }

    $lower_gids = array_slice($notifying_gids, 0, (int) $position, TRUE);

    // Which members of the current group also belong to a lower group. Read
    // as two columns rather than entities, and chunked: a large group would
    // otherwise put a five-figure placeholder list in one statement, which
    // fails outright rather than merely running slowly.
    $lower_gids_per_uid = [];
    foreach (array_chunk(array_keys($member_accounts), self::MEMBER_QUERY_CHUNK_SIZE) as $uid_chunk) {
      $query = $this->database->select('group_relationship_field_data', 'gc');
      $query->addField('gc', 'gid');
      $query->addField('gc', 'entity_id');
      $query->condition('gc.gid', array_values($lower_gids), 'IN');
      $query->condition('gc.plugin_id', 'group_membership');
      $query->condition('gc.entity_id', $uid_chunk, 'IN');

      $statement = $query->execute();

      if ($statement === NULL) {
        continue;
      }

      foreach ($statement as $row) {
        $lower_gids_per_uid[(int) $row->entity_id][] = (int) $row->gid;
      }
    }

    if ($lower_gids_per_uid === []) {
      return [];
    }

    $lower_groups = $this->entityTypeManager->getStorage('group')
      ->loadMultiple(array_values($lower_gids));

    // @todo Prefetch the mute flaggings for these groups and users in one
    // query. Doing it here would mean re-implementing the rule that
    // GroupMuteNotify owns, so it belongs behind a bulk method on that
    // service rather than in this loop.
    $covered_uids = [];
    foreach ($lower_gids_per_uid as $uid => $gids) {
      sort($gids);

      foreach ($gids as $gid) {
        $lower_group = $lower_groups[$gid] ?? NULL;

        if (
          $lower_group instanceof GroupInterface &&
          !$this->groupMuteNotify->groupNotifyIsMuted($lower_group, $member_accounts[$uid])
        ) {
          $covered_uids[$uid] = $uid;
          break;
        }
      }
    }

    return $covered_uids;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity): bool {
    if ($entity instanceof GroupRelationshipInterface) {
      return TRUE;
    }

    if ($entity instanceof PostInterface) {
      return $entity->hasField("field_recipient_group") && !$entity->get("field_recipient_group")->isEmpty();
    }

    return FALSE;
  }

}

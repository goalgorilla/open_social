<?php

namespace Drupal\social_group;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\node\Entity\Node;
use Drupal\social_post\Entity\Post;

/**
 * Class SocialGroupHelperService.
 *
 * @package Drupal\social_group
 */
class SocialGroupHelperService {

  /**
   * A cache of groups that have been matched to entities.
   *
   * @var array
   */
  protected $cache;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor for SocialGroupHelperService.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * Returns a group id from a entity (post, node).
   *
   * @param array $entity
   *   The entity in the form of an entity reference array to get the group for.
   * @param bool $read_cache
   *   Whether the per request cache should be used. This should only be
   *   disabled if you know that the group for the entity has changed because
   *   disabling this can have serious performance implications. Setting this to
   *   FALSE will update the cache for subsequent calls.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group that this entity belongs to or NULL if the entity doesn't
   *   belong to any group.
   */
  public function getGroupFromEntity(array $entity, $read_cache = TRUE) {
    $gid = NULL;

    // Comments can have groups based on what the comment is posted on so the
    // cache type differs from what we later use to fetch the group.
    $cache_type = $entity['target_type'];
    $cache_id = $entity['target_id'];

    if ($read_cache && is_array($this->cache) && is_array($this->cache[$cache_type]) && isset($this->cache[$cache_type][$cache_id])) {
      return $this->cache[$cache_type][$cache_id];
    }

    // Special cases for comments.
    // Returns the entity to which the comment is attached.
    if ($entity['target_type'] === 'comment') {
      $comment = \Drupal::entityTypeManager()
        ->getStorage('comment')
        ->load($entity['target_id']);
      $commented_entity = $comment->getCommentedEntity();
      $entity['target_type'] = $commented_entity->getEntityTypeId();
      $entity['target_id'] = $commented_entity->id();
    }

    if ($entity['target_type'] === 'post') {
      /** @var /Drupal/social_post/Entity/Post $post */
      $post = Post::load($entity['target_id']);
      $recipient_group = $post->get('field_recipient_group')->getValue();
      if (!empty($recipient_group)) {
        $gid = $recipient_group['0']['target_id'];
      }
    }
    elseif ($entity['target_type'] === 'node') {
      // Try to load the entity.
      if ($node = Node::load($entity['target_id'])) {
        // Try to load group content from entity.
        if ($groupcontent = GroupContent::loadByEntity($node)) {
          // Potentially there are more than one.
          $groupcontent = reset($groupcontent);
          // Set the group id.
          $gid = $groupcontent->getGroup()->id();
        }
      }
    }

    // Cache the group id for this entity to optimise future calls.
    $this->cache[$cache_type][$cache_id] = $gid;

    return $gid;
  }

  /**
   * Returns the default visibility.
   *
   * @param string $type
   *   The Group Type.
   *
   * @return string|null
   *   The default visibility.
   */
  public static function getDefaultGroupVisibility($type) {
    $visibility = &drupal_static(__FUNCTION__ . $type);

    if (empty($visibility)) {
      switch ($type) {
        case 'closed_group':
          $visibility = 'group';
          break;

        case 'open_group':
          $visibility = 'community';
          break;

        case 'public_group':
          $visibility = 'public';
          break;

        default:
          $visibility = NULL;
      }

      \Drupal::moduleHandler()
        ->alter('social_group_default_visibility', $visibility, $type);
    }

    return $visibility;
  }

  /**
   * Returns the statically cached group members form the current group.
   *
   * @return array
   *   All group members as array with value user->id().
   */
  public static function getCurrentGroupMembers() {
    $cache = &drupal_static(__FUNCTION__, []);

    if (!empty($cache)) {
      return $cache;
    }

    $group = _social_group_get_current_group();
    if ($group instanceof GroupInterface) {
      $memberships = $group->getMembers();
      foreach ($memberships as $member) {
        $cache[] = $member->getUser()->id();
      }
    }

    return $cache;
  }

  /**
   * Get all group memberships for a certain user.
   *
   * @param int $uid
   *   The UID for which we fetch the groups it is member of.
   *
   * @return array
   *   List of group IDs the user is member of.
   */
  public function getAllGroupsForUser($uid) {
    $groups = &drupal_static(__FUNCTION__);

    // Get the memberships for the user if they aren't known yet.
    if (!isset($groups[$uid])) {
      $group_content_types = GroupContentType::loadByEntityTypeId('user');
      $group_content_types = array_keys($group_content_types);

      $query = $this->database->select('group_content_field_data', 'gcfd');
      $query->addField('gcfd', 'gid');
      $query->condition('gcfd.entity_id', $uid);
      $query->condition('gcfd.type', $group_content_types, 'IN');
      $query->execute()->fetchAll();

      $group_ids = $query->execute()->fetchAllAssoc('gid');
      $groups[$uid] = array_keys($group_ids);
    }

    return $groups[$uid];
  }

  /**
   * Get the add group URL for given user.
   *
   * This returns either /group/add or /group/add/{group_type}
   * depending upon the permission of the user to create group.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Url
   *   URL of the group add page.
   */
  public function getGroupsToAddUrl(AccountInterface $account) {
    $url = NULL;
    $user_can_create_groups = [];
    // Get all available group types.
    foreach (GroupType::loadMultiple() as $group_type) {
      // When the user has permission to create a group of the current type, add
      // this to the create group array.
      if ($account->hasPermission('create ' . $group_type->id() . ' group')) {
        $user_can_create_groups[$group_type->id()] = $group_type;
      }

      if (count($user_can_create_groups) > 1) {
        break;
      }
    }

    // There's just one group this user can create.
    if (count($user_can_create_groups) === 1) {
      // When there is only one group allowed, add create the url to create a
      // group of this type.
      $allowed_group_type = reset($user_can_create_groups);
      /** @var \Drupal\group\Entity\Group $allowed_group_type */
      $url = Url::fromRoute('entity.group.add_form', [
        'group_type' => $allowed_group_type->id(),
      ]);
    }
    return $url;
  }

}

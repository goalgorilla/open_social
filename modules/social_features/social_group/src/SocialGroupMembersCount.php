<?php

namespace Drupal\social_group;

use Drupal\Core\Database\Connection;
use Drupal\group\Entity\GroupInterface;

/**
 * Class SocialGroupMembersCount.
 *
 * @package Drupal\social_group
 */
class SocialGroupMembersCount {

  /**
   * A cache of group members count for a specific group.
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
   * Constructor for SocialGroupMembersCount.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * Get group members count.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param bool $read_cache
   *   Whether the per request cache should be used.
   *
   * @return int
   *   Number of members in a group.
   */
  public function getGroupMemberCount(GroupInterface $group, $read_cache = TRUE) {
    $cache_type = $group->getGroupType()->id();
    $cache_id = $group->id();

    if ($read_cache && is_array($this->cache[$cache_type]) && isset($this->cache[$cache_type][$cache_id])) {
      return $this->cache[$cache_type][$cache_id];
    }

    $query = $this->database->select('group_content_field_data', 'gcfd');
    $query->addField('gcfd', 'gid');
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $group->getGroupType()->id() . '-group_membership', 'LIKE');
    $count = $query->countQuery()->execute()->fetchField();

    // Cache the group id for this entity to optimise future calls.
    $this->cache[$cache_type][$cache_id] = $count;

    return $count;
  }

}

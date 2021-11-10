<?php

namespace Drupal\social_group;

use Drupal\Core\Database\Connection;
use Drupal\group\Entity\GroupInterface;

/**
 * Group Statistics.
 *
 * @package Drupal\social_group
 */
class GroupStatistics {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

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
   *
   *   The number of members.
   */
  public function getGroupMemberCount(GroupInterface $group): int {
    return $this->count($group, 'group_membership');
  }

  /**
   * Get group node count by type.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param string $type
   *   Node type id.
   *
   *   The number of nodes.
   */
  public function getGroupNodeCount(GroupInterface $group, string $type): int {
    return $this->count($group, 'group_node-' . $type);
  }

  /**
   * Get entity count by type for the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param string $type
   *   Entity type in group.
   *
   *   The number of entities.
   */
  protected function count(GroupInterface $group, string $type): int {
    // Additional caching not required since views does this for us.
    $query = $this->database->select('group_content_field_data', 'gcfd');
    $query->addField('gcfd', 'gid');
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $group->getGroupType()->id() . '-' . $type, 'LIKE');

    return $query->countQuery()->execute()->fetchField();
  }

}

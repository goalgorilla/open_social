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
   *
   * @return int
   *   Number of members in a group.
   */
  public function getGroupMemberCount(GroupInterface $group) {
    // Additional caching not required since views does this for us.
    $query = $this->database->select('group_content_field_data', 'gcfd');
    $query->addField('gcfd', 'gid');
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $group->getGroupType()->id() . '-group_membership', 'LIKE');

    return $query->countQuery()->execute()->fetchField();
  }

}

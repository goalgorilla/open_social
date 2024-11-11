<?php

namespace Drupal\social_group;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Class GroupStatistics.
 *
 * @package Drupal\social_group
 */
class GroupStatistics {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for SocialGroupMembersCount.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager interface.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get group members count.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return int
   *   The number of members.
   */
  public function getGroupMemberCount(GroupInterface $group) {
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
   * @return int
   *   The number of nodes.
   */
  public function getGroupNodeCount(GroupInterface $group, $type) {
    return $this->count($group, 'group_node:' . $type);
  }

  /**
   * Get entity count by type for the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param string $type
   *   Entity type in group.
   *
   * @return int
   *   The number of entities.
   */
  protected function count(GroupInterface $group, $type) {
    // Additional caching not required since views does this for us.
    return $this->entityTypeManager->getStorage('group_content')->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('plugin_id', $type)
      ->count()
      ->execute();
  }

}

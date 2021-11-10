<?php

namespace Drupal\social_profile;

use Drupal\Core\Database\Connection;

/**
 * User Statistics class.
 *
 * @package Drupal\social_profile
 */
class UserStatistics {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructor for UserStatistics.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * Get node count by type.
   *
   * @param int $user_id
   *   The user ID.
   * @param string $type
   *   Node type id.
   *
   *   The number of nodes.
   */
  public function nodeCount(int $user_id, string $type): int {
    return $this->count($user_id, $type);
  }

  /**
   * Get entity count by type for the profile.
   *
   * @param int $user_id
   *   The user ID.
   * @param string $type
   *   Entity type.
   *
   *   The number of entities.
   */
  protected function count(int $user_id, string $type): int {
    $query = $this->database->select('node_field_data', 'nfd');
    $query->addField('nfd', 'nid');
    $query->condition('nfd.uid', $user_id);
    $query->condition('nfd.type', $type, 'LIKE');

    return $query->countQuery()->execute()->fetchField();
  }

}

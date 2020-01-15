<?php

namespace Drupal\social_profile;

use Drupal\Core\Database\Connection;

/**
 * Class UserStatistics.
 *
 * @package Drupal\social_profile
 */
class UserStatistics {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * @return int
   *   The number of nodes.
   */
  public function nodeCount($user_id, $type) {
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
   * @return int
   *   The number of entities.
   */
  protected function count($user_id, $type) {
    $query = $this->database->select('node_field_data', 'nfd');
    $query->addField('nfd', 'nid');
    $query->condition('nfd.uid', $user_id);
    $query->condition('nfd.type', $type, 'LIKE');

    return $query->countQuery()->execute()->fetchField();
  }

}

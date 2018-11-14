<?php

/**
 * @file
 * Executes an update which is intended to update data, like entities.
 */

use Drupal\Core\Database\Database;

/**
 * Trigger clean up functions for orphaned posts.
 */
function social_post_post_update_remove_orphaned_posts() {
  $connection = Database::getConnection();

  // Inner select of all users for the WHERE clause.
  $user_query = $connection->select('users', 'u')
    ->fields('u', ['uid']);

  // Find the user ids of deleted users where posts were left behind.
  $result = Database::getConnection()
    ->select('post_field_data', 'p')
    ->fields('p', ['id'])
    ->condition('user_id', $user_query, 'NOT IN')
    ->execute()
    ->fetchAll();

  $pids = [];
  foreach ($result as $row) {
    $pids[] = $row->id;
  }

  \Drupal::logger('social_post')->info('Removing @count orphaned posts for deleted users', ['@count' => count($pids)]);

  $storage_handler = \Drupal::entityTypeManager()->getStorage('post');
  $entities = $storage_handler->loadMultiple($pids);
  $storage_handler->delete($entities);
}

<?php

/**
 * @file
 * Post update functions for social_group_request module.
 */

declare(strict_types=1);

use Drupal\Core\Site\Settings;

/**
 * Delete pending membership requests for non-existent users.
 *
 * These orphans inflate the notification on group pages while
 * the corresponding view excludes them.
 */
function social_group_request_post_update_delete_orphaned_membership_requests(array &$sandbox): string {
  $database = \Drupal::database();
  $storage = \Drupal::entityTypeManager()->getStorage('group_content');

  if (!isset($sandbox['ids'])) {
    $query = $database->select('group_content__grequest_status', 's');
    $query->innerJoin('group_relationship_field_data', 'g', 'g.id = s.entity_id');
    $query->leftJoin('users_field_data', 'u', 'u.uid = g.entity_id');
    $query->fields('g', ['id']);
    $query->condition('g.plugin_id', 'group_membership_request');
    $query->condition('s.grequest_status_value', 'pending');
    $query->isNull('u.uid');
    $result = $query->execute();
    $ids = $result !== NULL ? $result->fetchCol() : [];

    $sandbox['ids'] = array_values(array_map('intval', $ids));
    $sandbox['max'] = count($sandbox['ids']);
    $sandbox['progress'] = 0;
  }

  if ($sandbox['max'] === 0) {
    $sandbox['#finished'] = 1;
    return 'No orphaned pending membership requests found.';
  }

  $batch_size = (int) Settings::get('entity_update_batch_size', 50);
  $slice = array_slice($sandbox['ids'], $sandbox['progress'], $batch_size);

  if ($slice) {
    $entities = $storage->loadMultiple($slice);
    if ($entities) {
      $storage->delete($entities);
    }
    $sandbox['progress'] += count($slice);
  }
  else {
    $sandbox['progress'] = $sandbox['max'];
  }

  $sandbox['#finished'] = $sandbox['progress'] / $sandbox['max'];

  return sprintf(
    'Deleted %d of %d orphaned pending membership requests.',
    $sandbox['progress'],
    $sandbox['max']
  );
}

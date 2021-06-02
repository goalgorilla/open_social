<?php

/**
 * @file
 * Post update functions for Entity Access By Field.
 */

use Drupal\Core\Entity\EntityStorageException;
use Drupal\search_api\Entity\Index;

/**
 * Rebuild node access.
 */
function entity_access_by_field_post_update_10101_rebuild_node_access() {
  node_access_rebuild(TRUE);
}

/**
 * Update Search index.
 */
function entity_access_by_field_post_update_10102_update_search_index() {
  try {
    $indexes = [
      'social_all',
      'social_content',
    ];

    foreach ($indexes as $index) {
      $index = Index::load($index);
      if ($index !== NULL && $index->status()) {
        $index->save();
        $index->clear();
        $index->reindex();
      }
    }
  }
  catch (EntityStorageException $e) {
    \Drupal::logger('entity_access_by_field')->info($e->getMessage());
  }
}

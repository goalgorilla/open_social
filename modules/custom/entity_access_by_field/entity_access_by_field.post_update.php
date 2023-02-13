<?php

/**
 * @file
 * Post update functions for Entity Access By Field.
 */

/**
 * Rebuild node access.
 */
function entity_access_by_field_post_update_10101_rebuild_node_access() {
  // Removing code as in favor of revert.
  // @see https://github.com/goalgorilla/open_social/pull/2438
}

/**
 * Rebuild node access.
 */
function entity_access_by_field_post_update_10102_rebuild_node_access() {
  node_access_rebuild(TRUE);
}

/**
 * Reindex items in the 'social_all' and 'social_content'.
 */
function entity_access_by_field_post_update_11001_reindex_search(array &$sandbox): void {
  /** @var \Drupal\search_api\IndexInterface[] $search_api_indexes */
  $search_api_indexes = \Drupal::entityTypeManager()
    ->getStorage('search_api_index')
    ->loadMultiple([
      'social_all',
      'social_content',
    ]);
  foreach ($search_api_indexes as $index_id => $index) {
    $index->clear();
  }

  // Removed reindexing because it could cause a problems with a search server
  // if platforms has a lot of items to be indexed. Let's make cron do the work
  // instead of doing it ourselves.
}

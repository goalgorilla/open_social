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
  if (!isset($sandbox['total'])) {
    /** @var \Drupal\search_api\IndexInterface[] $search_api_indexes */
    $search_api_indexes = \Drupal::entityTypeManager()
      ->getStorage('search_api_index')
      ->loadMultiple([
        'social_all',
        'social_content',
      ]);

    $sandbox['total'] = 0;
    foreach ($search_api_indexes as $index_id => $index) {
      $index->clear();
      $sandbox[$index_id] = [
        'not_indexed' => $index->hasValidTracker() ? $index->getTrackerInstance()->getRemainingItemsCount() : 0,
      ];
      $sandbox['total'] += $sandbox[$index_id]['not_indexed'];
    }

    $sandbox['indexes'] = $search_api_indexes;
    $sandbox['total_indexed'] = 0;
  }

  if (!empty($sandbox['indexes'])) {
    /** @var \Drupal\search_api\IndexInterface $current_index */
    $current_index = reset($sandbox['indexes']);
    $current_index_id = $current_index->id();
    if ($sandbox[$current_index_id]['not_indexed'] <= 0) {
      unset($sandbox['indexes'][$current_index_id]);
    }

    if (isset($sandbox['indexes'][$current_index_id])) {
      $indexed = $current_index->indexItems(25);
      $sandbox[$current_index_id]['not_indexed'] -= $indexed;
      $sandbox['total_indexed'] += $indexed;
    }

    $sandbox['#finished'] = empty($sandbox['total']) ? 1 : ($sandbox['total_indexed'] / $sandbox['total']);
  }
}

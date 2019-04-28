<?php

/**
 * @file
 * Hooks provided by the Social Content Block module.
 */

/**
 * Provide a method to alter the query to get content.
 *
 * @param \Drupal\Core\Database\Driver\mysql\Select $query
 *   Query to alter the results.
 * @param BlockContent $blockContent
 *   Current block content.
 *
 * @ingroup social_content_block_api
 */
function hook_social_content_block_query_alter(\Drupal\Core\Database\Driver\mysql\Select $query, BlockContent $blockContent) {
  // Get topic type tags.
  $topic_types_list = $blockContent->get('field_topic_type')->getValue();
  $topic_types = array_map(function ($topic_type) {
    return $topic_type['target_id'];
  }, $topic_types_list);

  // Add topic type tags.
  if (!empty($topic_types)) {
    $query->innerJoin('node__field_topic_type', 'tt', 'tt.entity_id = n.nid');
    $query->condition('tt.field_topic_type_target_id', $topic_types, 'IN');
  }
}

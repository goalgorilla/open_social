<?php

/**
 * @file
 * Hooks provided by the Social Content Block module.
 */

/**
 * Provide a method to alter the query to get content.
 *
 * @param array $query
 *   Query to alter the results.
 *
 * @ingroup social_content_block_api
 */
function hook_social_content_block_query_alter(array &$query) {
  $query->innerJoin('node__field_topic_type', 'tt', 'tt.entity_id = n.nid');
  $query->condition('tt.field_topic_type_target_id', '1', 'IN');
}

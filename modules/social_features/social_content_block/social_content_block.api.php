<?php

/**
 * @file
 * Hooks provided by the Social Content Block module.
 */

use Drupal\Core\Database\Driver\mysql\Select;
use Drupal\block_content\BlockContentInterface;

/**
 * Provide a method to alter the query to get content.
 *
 * @param \Drupal\Core\Database\Driver\mysql\Select $query
 *   Query to alter the results.
 * @param \Drupal\block_content\BlockContentInterface $block_content
 *   Current block content.
 *
 * @ingroup social_content_block_api
 */
function hook_social_content_block_query_alter(Select $query, BlockContentInterface $block_content) {
  // Get topic type tags.
  $topic_types_list = $block_content->get('field_topic_type')->getValue();
  $topic_types = array_map(function ($topic_type) {
    return $topic_type['target_id'];
  }, $topic_types_list);

  // Add topic type tags.
  if (!empty($topic_types)) {
    $query->innerJoin('node__field_topic_type', 'tt', 'tt.entity_id = n.nid');
    $query->condition('tt.field_topic_type_target_id', $topic_types, 'IN');
  }
}

/**
 * Alter the list of content block plugin definitions.
 *
 * @param array $info
 *   The content block plugin definitions to be altered.
 *
 * @see \Drupal\social_content_block\Annotation\ContentBlock
 * @see \Drupal\social_content_block\ContentBlockManager
 */
function hook_social_content_block_info_alter(array &$info) {
  if (isset($info['event_content_block'])) {
    $info['event_content_block']['fields'][] = 'field_content_tags';
  }
}

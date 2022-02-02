<?php

/**
 * @file
 * Hooks provided by the Social Content Block module.
 */

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Provides a method to alter the query to get content.
 *
 * @param \Drupal\Core\Database\Query\SelectInterface $query
 *   The query to alter the results.
 * @param \Drupal\block_content\BlockContentInterface $block_content
 *   The current block content.
 *
 * @deprecated in social:11.1.0 and is removed from social:12.0.0. Use
 *   hook_query_social_content_block_alter instead.
 *
 * @see https://www.drupal.org/node/3258202
 *
 * @ingroup social_content_block_api
 */
function hook_social_content_block_query_alter(SelectInterface $query, BlockContentInterface $block_content): void {
  /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
  $field = $block_content->field_topic_type;

  // Add topic type tags.
  if (!$field->isEmpty()) {
    $alias = $query->innerJoin(
      'node__field_topic_type',
      'tt',
      '%alias.entity_id = n.nid',
    );

    $query->condition(
      "$alias.field_topic_type_target_id",
      array_column($field->getValue(), 'target_id'),
      'IN',
    );
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

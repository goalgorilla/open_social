<?php

/**
 * @file
 * Hooks provided by the Social Content Block module.
 */

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

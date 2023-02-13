<?php

/**
 * @file
 * Contains post-update hooks for the Social Album module.
 */

/**
 * Updates the node type visibility condition.
 */
function social_album_post_update_replace_node_type_condition(): void {
  $config_factory = \Drupal::configFactory();

  $block = $config_factory->getEditable('block.block.socialblue_album_count_and_add');

  if ($block->get('visibility.node_type')) {
    $configuration = $block->get('visibility.node_type');
    $configuration['id'] = 'entity_bundle:node';
    $block->set('visibility.entity_bundle:node', $configuration);
    $block->clear('visibility.node_type');
    $block->save(TRUE);
  }
}

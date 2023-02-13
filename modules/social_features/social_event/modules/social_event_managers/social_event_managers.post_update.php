<?php

/**
 * @file
 * Contains post-update hooks for the Social Event Managers module.
 */

/**
 * Updates the node type visibility condition.
 */
function social_event_managers_post_update_replace_node_type_condition(): void {
  $config_factory = \Drupal::configFactory();

  $block_list = [
    'block.block.views_block__managers_event_managers',
    'block.block.views_block__managers_event_managers_2',
  ];

  foreach ($block_list as $block_config_name) {
    $block = $config_factory->getEditable($block_config_name);

    if ($block->get('visibility.node_type')) {
      $configuration = $block->get('visibility.node_type');
      $configuration['id'] = 'entity_bundle:node';
      $block->set('visibility.entity_bundle:node', $configuration);
      $block->clear('visibility.node_type');
      $block->save(TRUE);
    }
  }
}

<?php

/**
 * @file
 * Contains post-update hooks for the Social Sharing module.
 */

/**
 * Updates the node type visibility condition.
 */
function social_sharing_managers_post_update_replace_node_type_condition(): void {
  $config_factory = \Drupal::configFactory();

  $block_list = [
    'block.block.shariffsharebuttons',
    'block.block.shariffsharebuttons_2',
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

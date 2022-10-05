<?php

/**
 * @file
 * Contains post-update hooks for the Social Core module.
 */

/**
 * Enable the queue storage entity module.
 */
function social_core_post_update_8701_enable_queue_storage() {
  \Drupal::service('module_installer')->install([
    'social_queue_storage',
  ]);
}

/**
 * Enable the select2 module.
 */
function social_core_post_update_8702_enable_select2() {
  \Drupal::service('module_installer')->install([
    'select2',
  ]);
}

/**
 * Updates the node type visibility condition.
 */
function social_core_post_update_replace_node_type_condition(): void {
  $config_factory = \Drupal::configFactory();

  $block_list = [
    'block.block.socialbase_pagetitleblock',
    'block.block.socialblue_pagetitleblock',
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

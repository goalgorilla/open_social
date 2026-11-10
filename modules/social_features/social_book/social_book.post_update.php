<?php

/**
 * @file
 * Contains post-update hooks for the Social Book module.
 */

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Updates the node type visibility condition.
 */
function social_book_post_update_replace_node_type_condition(): void {
  $config_factory = \Drupal::configFactory();

  $block_list = [
    'block.block.booknavigation',
    'block.block.booknavigation_2',
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

/**
 * Show the book navigation on book pages again.
 *
 * Core Book always added the book navigation to the full view of book pages.
 * The contributed Book module only adds it when the display enables its
 * book_navigation component, and its own post update hides that component on
 * every node display. Enable it on the default book display, which renders the
 * full view of book pages.
 */
function social_book_post_update_enable_book_navigation_component(): void {
  $display = \Drupal::entityTypeManager()
    ->getStorage('entity_view_display')
    ->load('node.book.default');
  if (!$display instanceof EntityViewDisplayInterface) {
    return;
  }

  $display->setComponent('book_navigation', [
    'weight' => 100,
    'region' => 'content',
    'settings' => [],
    'third_party_settings' => [],
  ]);
  $display->save();
}

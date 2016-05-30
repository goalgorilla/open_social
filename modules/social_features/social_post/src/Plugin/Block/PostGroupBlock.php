<?php

/**
 * @file
 * Contains \Drupal\social_post\Plugin\Block\PostGroupBlock.
 */

namespace Drupal\social_post\Plugin\Block;

/**
 * Provides a 'PostGroupBlock' block.
 *
 * @Block(
 *  id = "post_group_block",
 *  admin_label = @Translation("Post on group block"),
 * )
 */
class PostGroupBlock extends PostBlock {

  public $entity_type;
  public $bundle;
  public $form_display;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity_type = 'post';
    $this->bundle = 'post';
    $this->form_display = 'group';
  }
}

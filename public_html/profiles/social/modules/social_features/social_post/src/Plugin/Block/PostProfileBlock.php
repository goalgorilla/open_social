<?php

/**
 * @file
 * Contains \Drupal\social_post\Plugin\Block\PostProfileBlock.
 */

namespace Drupal\social_post\Plugin\Block;

/**
 * Provides a 'PostProfileBlock' block.
 *
 * @Block(
 *  id = "post_profile_block",
 *  admin_label = @Translation("Post on profile block"),
 * )
 */
class PostProfileBlock extends PostBlock {

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
    $this->form_display = 'default';
  }

}

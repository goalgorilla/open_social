<?php

namespace Drupal\social_post_photo\Plugin\Block;

use Drupal\social_post\Plugin\Block\PostGroupBlock;

/**
 * Provides a 'PostPhotoGroupBlock' block.
 *
 * @Block(
 *  id = "post_photo_group_block",
 *  admin_label = @Translation("Post photo on group block"),
 * )
 */
class PostPhotoGroupBlock extends PostGroupBlock {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Override the bundle and the formDisplay.
    $this->bundle= 'photo';
  }
}

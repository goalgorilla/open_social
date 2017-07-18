<?php

namespace Drupal\social_post_photo\Plugin\Block;

use Drupal\social_post\Plugin\Block\PostBlock;

/**
 * Provides a 'PostPhotoBlock' block.
 *
 * @Block(
 *  id = "post_photo_block",
 *  admin_label = @Translation("Post photo block"),
 * )
 */
class PostPhotoBlock extends PostBlock {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Override the bundle type.
    $this->bundle = 'photo';
  }

}

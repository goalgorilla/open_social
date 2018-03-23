<?php

namespace Drupal\social_post_photo\Plugin\Block;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\social_post\Plugin\Block\PostProfileBlock;

/**
 * Provides a 'PostPhotoProfileBlock' block.
 *
 * @Block(
 *  id = "post_photo_profile_block",
 *  admin_label = @Translation("Post photo on profile of others block"),
 * )
 */
class PostPhotoProfileBlock extends PostProfileBlock {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entityTypeManager, $currentUser, $formBuilder, ModuleHandler $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager, $currentUser, $formBuilder, $moduleHandler);
    // Override the bundle type.
    $this->bundle = 'photo';
  }

}

<?php

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

  public $entityType;
  public $bundle;
  public $formDisplay;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'group';
  }

}

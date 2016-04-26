<?php

/**
 * @file
 * Contains \Drupal\social_search\Plugin\Block\SearchContentBlock.
 */

namespace Drupal\social_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SearchContentBlock' block.
 *
 * @Block(
 *  id = "search_content_block",
 *  admin_label = @Translation("Search content block"),
 * )
 */
class SearchContentBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $form = \Drupal::formBuilder()->getForm('Drupal\social_search\Form\SearchContentForm');
    $build['search_content_form'] = $form;

    return $build;
  }

}

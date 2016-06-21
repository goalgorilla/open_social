<?php

namespace Drupal\social_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SearchHeroContentBlock' block.
 *
 * @Block(
 *  id = "search_hero_content_block",
 *  admin_label = @Translation("Search hero content block"),
 * )
 */
class SearchHeroContentBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $form = \Drupal::formBuilder()->getForm('Drupal\social_search\Form\SearchHeroContentForm');
    $build['search_content_form'] = $form;

    return $build;
  }

}

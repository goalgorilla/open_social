<?php

namespace Drupal\social_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'SearchHeroBlock' block.
 *
 * @Block(
 *   id = "search_hero_block",
 *   admin_label = @Translation("Search hero block"),
 * )
 */
class SearchHeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $form = \Drupal::formBuilder()->getForm('Drupal\social_search\Form\SearchHeroForm');
    $build['search_form'] = $form;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url']);
  }

}

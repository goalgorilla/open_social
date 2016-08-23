<?php

namespace Drupal\social_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SearchGroupsBlock' block.
 *
 * @Block(
 *  id = "search_groups_block",
 *  admin_label = @Translation("Search groups block"),
 * )
 */
class SearchGroupsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $form = \Drupal::formBuilder()->getForm('Drupal\social_search\Form\SearchGroupsForm');
    $build['search_groups_form'] = $form;

    return $build;
  }

}

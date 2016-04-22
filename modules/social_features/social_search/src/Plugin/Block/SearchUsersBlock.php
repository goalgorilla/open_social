<?php

/**
 * @file
 * Contains \Drupal\social_search\Plugin\Block\SearchUsersBlock.
 */

namespace Drupal\social_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SearchUsersBlock' block.
 *
 * @Block(
 *  id = "search_users_block",
 *  admin_label = @Translation("Search users block"),
 * )
 */
class SearchUsersBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $form = \Drupal::formBuilder()->getForm('Drupal\social_search\Form\SearchUsersForm');
    $build['search_users_form'] = $form;

    return $build;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\social_profile\Plugin\Block\SearchUsersBlock.
 */

namespace Drupal\social_profile\Plugin\Block;

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

    $form = \Drupal::formBuilder()->getForm('Drupal\social_profile\Form\SearchUsersForm');
    $build['search_users_form'] = $form;

    return $build;
  }

}

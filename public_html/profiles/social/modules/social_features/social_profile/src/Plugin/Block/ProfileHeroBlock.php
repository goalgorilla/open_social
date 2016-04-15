<?php

/**
 * @file
 * Contains \Drupal\social_profile\Plugin\Block\ProfileHeroBlock.
 */

namespace Drupal\social_profile\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\profile\Entity\Profile;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a 'ProfileHeroBlock' block.
 *
 * @Block(
 *  id = "profile_hero_block",
 *  admin_label = @Translation("Profile hero block"),
 * )
 */
class ProfileHeroBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $account = \Drupal::routeMatch()->getParameter('user');
    if (!is_object($account)) {
      $account = \Drupal::service('entity_type.manager')->getStorage('user')->load($account);
    }

    if (!empty($account)) {
      $profile = social_profile_load_by_uid($account->id());
      if (empty($profile)) {
        //@TODO: Remove this part when all users will have profile by default.
        $content = array(
          '#theme' => 'username',
          '#account' => $account,
          '#prefix' => '<h1 class="page-title">',
          '#suffix' => '</h1>',
        );
      }
      else {
        $content = \Drupal::entityTypeManager()->getViewBuilder('profile')->view($profile, 'hero');
      }
    }

    $build['content'] = $content;
    $build['#cache'] = array(
      'max-age' => 0,
    );

    return $build;
  }

}

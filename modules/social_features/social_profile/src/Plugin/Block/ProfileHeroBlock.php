<?php

/**
 * @file
 * Contains \Drupal\social_profile\Plugin\Block\ProfileHeroBlock.
 */

namespace Drupal\social_profile\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
      $account = \Drupal::service('entity_type.manager')
        ->getStorage('user')
        ->load($account);
    }

    if (!empty($account)) {
      $storage = \Drupal::entityTypeManager()->getStorage('profile');
      if (!empty($storage)) {
        $user_profile = $storage->loadByUser($account, 'profile');
        if ($user_profile) {
          $content = \Drupal::entityTypeManager()
            ->getViewBuilder('profile')
            ->view($user_profile, 'hero');
          $build['content'] = $content;
        }
      }
    }

    $build['#cache'] = array(
      'max-age' => 0,
    );

    return $build;
  }

}

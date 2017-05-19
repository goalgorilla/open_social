<?php

namespace Drupal\social_profile\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'ProfileHeroBlock' block.
 *
 * @Block(
 *   id = "profile_hero_block",
 *   admin_label = @Translation("Profile hero block"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class ProfileHeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $account = $this->getContextValue('user');

    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    $profile = $storage->loadByUser($account, 'profile');

    if ($profile) {
      $build['content'] = \Drupal::entityTypeManager()
        ->getViewBuilder('profile')
        ->view($profile, 'hero');
      $build['content']['#cache']['tags'] = $this->getCacheTags();
      $build['content']['#cache']['contexts'] = $this->getCacheContexts();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $account = $this->getContextValue('user');
    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    $profile = $storage->loadByUser($account, 'profile');
    $tags = [
      'user:' . $account->id(),
    ];

    if ($profile) {
      $tags[] = 'profile:' . $profile->id();
    }

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user.permissions']);
  }

}

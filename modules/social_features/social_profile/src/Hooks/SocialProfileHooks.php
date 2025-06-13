<?php

namespace Drupal\social_profile\Hooks;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\Entity\Bundle\SocialProfile;

/**
 * Social profile hooks.
 *
 * @internal
 */
class SocialProfileHooks {

  /**
   * SocialProfileHooks constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    protected AccountInterface $current_user,
  ) {}

  /**
   * Bundle class manager.
   *
   * @param array $bundles
   *   Bundles array.
   *
   * @return void
   *   Return void.
   */
  #[Alter('entity_bundle_info')]
  public function socialProfileBundleClasses(array &$bundles): void {
    if (isset($bundles['profile']['profile'])) {
      $bundles['profile']['profile']['class'] = SocialProfile::class;
    }
  }

  /**
   * Create our custom profile tag so we can also invalidate f.e. teasers.
   *
   * @param array $build
   *   A render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity for which to update the tags.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display interface.
   *
   * @return void
   *   Return void.
   */
  #[Alter('profile_view')]
  public function socialProfileCustomTags(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    if ($this->current_user->isAnonymous() || !$entity instanceof Profile) {
      return;
    }

    $uid = $this->current_user->id();
    $pid = $entity->id();

    // Create our custom profile tag so we can also invalidate f.e. teasers.
    $profile_tag = 'profile:' . $pid . '-' . $uid;
    $build['#cache']['tags'][] = $profile_tag;
    $build['#cache']['contexts'][] = 'user';
  }

}

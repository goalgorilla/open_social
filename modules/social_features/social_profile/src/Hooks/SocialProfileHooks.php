<?php

namespace Drupal\social_profile\Hooks;

use Drupal\hux\Attribute\Alter;
use Drupal\social_profile\Entity\Bundle\SocialProfile;

/**
 * Social profile hooks.
 *
 * @internal
 */
class SocialProfileHooks {

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

}

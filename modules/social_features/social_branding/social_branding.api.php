<?php

/**
 * @file
 * Hooks provided by the Social Branding module.
 */

use Drupal\social_branding\PreferredPlatformFeature;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define social branding preferred features.
 *
 * @return array
 *   An array of PreferredPlatformFeature objects.
 *
 * @see hook_social_branding_preferred_features_alter()
 * @ingroup social_branding_api
 */
function hook_social_branding_preferred_features() {
  return [
    new PreferredPlatformFeature('first_feature', 1),
    new PreferredPlatformFeature('second_feature', 2),
  ];
}

/**
 * Perform alterations on social branding preferred features.
 *
 * @param array $preferred_features
 *   Array of PreferredPlatformFeature objects.
 *
 * @see hook_social_branding_preferred_features()
 * @ingroup social_branding_api
 */
function hook_social_branding_preferred_features_alter(array &$preferred_features) {
  foreach ($preferred_features as $preferred_feature) {
    if ($preferred_feature->getName() === 'first_feature') {
      $preferred_feature->setWeight(3);
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */

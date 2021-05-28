<?php

/**
 * @file
 * Post update functions for Social Profile.
 */

use Drupal\profile\Entity\ProfileInterface;

/**
 * Update Profile names.
 */
function social_profile_post_update_0001(&$sandbox) {
  /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
  $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');

  if (!isset($sandbox['count'])) {
    $sandbox['ids'] = \Drupal::entityQuery('profile')
      ->condition('type', 'profile')
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['count'] = count($sandbox['ids']);
  }

  $ids = array_splice($sandbox['ids'], 0, 50);

  foreach ($ids as $id) {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $profile_storage->load($id);
    if ($profile instanceof ProfileInterface) {
      // We need just save the profile. The profile name will be updated by
      // hook "presave".
      // @see social_profile_profile_presave()
      // @see social_profile_privacy_profile_presave()
      $profile->save();
    }
  }

  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : ($sandbox['count'] - count($sandbox['ids'])) / $sandbox['count'];
}

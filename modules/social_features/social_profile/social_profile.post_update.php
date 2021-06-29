<?php

/**
 * @file
 * Post update functions for Social Profile.
 */

use Drupal\profile\Entity\ProfileInterface;

/**
 * Add values to the new profile field "Profile name" for all existing users.
 */
function social_profile_post_update_10101_profile_names_update(&$sandbox) {
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

  // Load profiles by profiles IDs.
  $profiles = $profile_storage->loadMultiple($ids);

  /** @var \Drupal\social_profile\SocialProfileNameService $profile_name_service */
  $profile_name_service = \Drupal::service('social_profile.name_service');

  /** @var \Drupal\Core\Database\Connection $connection */
  $connection = \Drupal::service('database');

  // Values of the Profiles names.
  $values = [];

  /** @var \Drupal\profile\Entity\ProfileInterface $profile */
  foreach ($profiles as $profile) {
    if ($profile instanceof ProfileInterface) {
      // Get generated profile name.
      $profile_name = $profile_name_service->getProfileName($profile);
      // Add generated profile name.
      $values[] = [
        'profile',
        0,
        $profile->id(),
        $profile->id(),
        'und',
        0,
        $profile_name,
      ];
    }
  }

  // Add the Profile name field value directly by database insert to reduce
  // the update time of a large number of profiles.
  $query = $connection->insert('profile__profile_name')->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'profile_name_value',
  ]);
  foreach ($values as $record) {
    $query->values($record);
  }
  $query->execute();

  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : ($sandbox['count'] - count($sandbox['ids'])) / $sandbox['count'];
}

<?php

/**
 * @file
 * Contains post-update hooks for the Social User module.
 */

use Drupal\user\UserInterface;

/**
 * Convert navigation settings into the correct format.
 */
function social_user_post_update_convert_navigation_settings() {
  $config = \Drupal::configFactory()->getEditable('social_user.navigation.settings');
  $config->set('display_my_groups_icon', (bool) $config->get('display_my_groups_icon'));
  $config->set('display_social_private_message_icon', (bool) $config->get('display_social_private_message_icon'));
  $config->save();
}

/**
 * Add role "verified" to existing users.
 *
 * Give all existing users on the platform the new role to ensure backward
 * compatibility (even blocked users).
 *
 * @param string[] $sandbox
 *   Stores information for batch updates.
 *
 * @return bool
 *   TRUE if the operation was finished, FALSE otherwise.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function social_user_post_update_10101_add_verified_role_to_existing_users(array &$sandbox) : bool {
  /** @var \Drupal\user\UserStorageInterface $user_storage */
  $user_storage = \Drupal::entityTypeManager()->getStorage('user');

  if (!isset($sandbox['count'])) {
    // Get all user's IDs and set them to the sandbox.
    $sandbox['ids'] = \Drupal::entityQuery('user')
      ->accessCheck(FALSE)
      ->execute();

    $sandbox['count'] = count((array) $sandbox['ids']);
  }

  $sandbox['ids'] = (array) $sandbox['ids'];
  $ids = array_splice($sandbox['ids'], 0, 50);

  // Load accounts by users IDs.
  $accounts = $user_storage->loadMultiple($ids);

  /** @var \Drupal\Core\Session\AccountInterface $account */
  foreach ($accounts as $account) {
    if ($account instanceof UserInterface) {
      // Add role "verified".
      $account->addRole('verified');
      $account->save();
    }
  }

  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : (((int) $sandbox['count']) - count($sandbox['ids'])) / ($sandbox['count']);

  return (bool) $sandbox['#finished'];
}

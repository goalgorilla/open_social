<?php

/**
 * @file
 * Hooks specific to the Social Auth Extra module.
 */

use Drupal\user\UserInterface;
use Drupal\social_auth_extra\AuthManagerInterface;
use Drupal\social_auth_extra\UserManagerInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * This hook will called before creating a new account.
 *
 * For when a user is registering via social network.
 *
 * @param \Drupal\user\UserInterface $account
 *   The Account.
 * @param \Drupal\social_auth_extra\AuthManagerInterface $auth_manager
 *   The AuthManagerInterface.
 * @param \Drupal\social_auth_extra\UserManagerInterface $user_manager
 *   The UserManagerInterface.
 */
function hook_social_auth_extra_user_presave(UserInterface $account, AuthManagerInterface $auth_manager, UserManagerInterface $user_manager) {

}

/**
 * This hook will called before creating a new profile.
 *
 * For when a user is registering via social network.
 *
 * @param \Drupal\user\UserInterface $account
 *   The Account.
 * @param \Drupal\profile\Entity\ProfileInterface $profile
 *   The Profile.
 * @param \Drupal\social_auth_extra\AuthManagerInterface $auth_manager
 *   The AuthManagerInterface.
 * @param \Drupal\social_auth_extra\UserManagerInterface $user_manager
 *   The UserManagerInterface.
 */
function hook_social_auth_extra_profile_presave(UserInterface $account, ProfileInterface $profile, AuthManagerInterface $auth_manager, UserManagerInterface $user_manager) {

}

/**
 * @} End of "addtogroup hooks".
 */

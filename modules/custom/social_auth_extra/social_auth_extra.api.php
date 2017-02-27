<?php

/**
 * @file
 * Hooks specific to the Social Auth Extra module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * This hook will called before creating a new account when user is registering via social network.
 *
 * @param \Drupal\user\UserInterface $account
 * @param \Drupal\social_auth_extra\AuthManagerInterface $auth_manager
 * @param \Drupal\social_auth_extra\UserManagerInterface $user_manager
 */
function hook_social_auth_extra_user_presave(\Drupal\user\UserInterface $account, \Drupal\social_auth_extra\AuthManagerInterface $auth_manager, \Drupal\social_auth_extra\UserManagerInterface $user_manager) {

}

/**
 * This hook will called before creating a new profile when user is registering via social network.
 *
 * @param \Drupal\user\UserInterface $account
 * @param \Drupal\profile\Entity\ProfileInterface $profile
 * @param \Drupal\social_auth_extra\AuthManagerInterface $auth_manager
 * @param \Drupal\social_auth_extra\UserManagerInterface $user_manager
 */
function hook_social_auth_extra_profile_presave(\Drupal\user\UserInterface $account, \Drupal\profile\Entity\ProfileInterface $profile, \Drupal\social_auth_extra\AuthManagerInterface $auth_manager, \Drupal\social_auth_extra\UserManagerInterface $user_manager) {

}

/**
 * @} End of "addtogroup hooks".
 */

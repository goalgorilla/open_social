<?php

/**
 * @file
 * Hooks provided by the Social Follow User module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters user profile display modes where needs to show "Follow" button.
 *
 * @param array $displays
 *   An array of user profile display modes.
 */
function hook_social_follow_user_profile_modes_alter(array &$displays): void {
  $displays[] = 'teaser';
}

/**
 * @} End of "addtogroup hooks".
 */

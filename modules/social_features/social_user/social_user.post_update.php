<?php

/**
 * @file
 * Contains post-update hooks for the Social User module.
 */

/**
 * Convert navigation settings into the correct format.
 */
function social_user_post_update_convert_navigation_settings() {
  $config = \Drupal::configFactory()->getEditable('social_user.navigation.settings');
  $config->set('display_my_groups_icon', (bool) $config->get('display_my_groups_icon'));
  $config->set('display_social_private_message_icon', (bool) $config->get('display_social_private_message_icon'));
  $config->save();
}

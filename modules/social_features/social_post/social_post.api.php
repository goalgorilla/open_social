<?php

/**
 * @file
 * Hooks provided by the Social Group module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the icon and title for post visibility.
 *
 * @param string $visibility
 *   The current field_visibility value, "1" for 'Community' etc.
 *
 * @ingroup social_post_api
 */
function hook_social_post_visibility_info_alter($visibility, &$icon, &$title) {
  switch ($visibility) {
    case '5':
      $icon = 'community';
      $title = t('Community');
      break;

    case '6':
      $icon = 'lock';
      $title = t('Closed');
      break;
  }
}

/**
 * @} End of "addtogroup hooks".
 */

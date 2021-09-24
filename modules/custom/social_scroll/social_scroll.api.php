<?php

/**
 * @file
 * Hooks specific to the Social Scroll module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the array of allowed view ids for infinite scroll.
 *
 * @param string[] $view_ids
 *   List of allowed view ids.
 */
function hook_social_scroll_allowed_views_alter(array &$view_ids): void {
  $view_ids[] = 'user_admin_people';
}

/**
 * @} End of "addtogroup hooks".
 */

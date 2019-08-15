<?php

/**
 * @file
 * Hooks provided by the Social Group Flexible Group module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter an array of routes that require content visibility acces checks.
 *
 * @param array $content_routes
 *   List of routes that required flexible group content visibility checks.
 *
 * @ingroup social_group_api
 */
function hook_social_group_flexible_group_content_routes_alter(array &$content_routes) {
  $content_routes[] = 'view.group_members.page_group_members';
}

/**
 * @} End of "addtogroup hooks".
 */

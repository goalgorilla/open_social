<?php

/**
 * @file
 * Hooks provided by the Comment module.
 */

/**
 * @addtogroup hooks
 * @{
 *
 * @param array $types
 *   An array of all available group types.
 */
function hook_social_group_default_route_types_alter(array $types) {
  // Enable functionality for secret groups.
  $types[] = 'my_custom_group';
}

/**
 * Provide a method to alter array of non-member redirect routes.
 *
 * @param array $routes
 *   List of routes.
 *
 * @ingroup social_group_default_route_api
 */
function hook_social_group_default_route_non_member_routes_alter(array &$routes): void {
  $routes['my_custom_route'] = t('My custom route name');
}

/**
 * Provide a method to alter array of member redirect routes.
 *
 * @param array $routes
 *   List of routes.
 *
 * @ingroup social_group_default_route_api
 */
function hook_social_group_default_route_member_routes_alter(array &$routes): void {
  $routes['my_custom_route'] = t('My custom route name');
}

/**
 * @} End of "addtogroup hooks".
 */

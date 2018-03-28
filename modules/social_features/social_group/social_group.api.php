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
 * Provide a method to alter array of group types used in open social.
 *
 * @param array $social_group_types
 *   List of group types used in open social.
 *
 * @ingroup social_group_api
 */
function hook_social_group_types_alter(array &$social_group_types) {
  $social_group_types[] = 'challenge';
}

/**
 * Provide a method to alter default group overview route.
 *
 * @param array $route
 *   An array with route name and parameters.
 *
 * @param GroupInterface $group
 *   Current group entity.
 *
 * @ingroup social_group_api
 */
function hook_social_group_overview_route_alter(array &$route, GroupInterface $group) {
  $route = [
    'name' => 'view.challenges_user.page',
    'parameters' => ['user' => \Drupal::currentUser()->id()],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */

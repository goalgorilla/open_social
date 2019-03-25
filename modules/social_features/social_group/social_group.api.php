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
 * Provide a method to alter the default visibility used for a group type.
 *
 * @param string $visibility
 *   The visibility option that is default.
 * @param string $group_type_id
 *   The Group type we alter the visibility setting for.
 *
 * @ingroup social_group_api
 */
function hook_social_group_default_visibility_alter(&$visibility, $group_type_id) {
  switch ($group_type_id) {
    case 'custom_public_group':
      $visibility = 'public';

      break;

    case 'custom_open_group':
      $visibility = 'community';

      break;

    case 'custom_closed_group':
      $visibility = 'group';

      break;
  }
}

/**
 * Provide a method to alter default group overview route.
 *
 * @param array $route
 *   An array with route name and parameters.
 * @param GroupInterface $group
 *   Current group entity.
 *
 * @ingroup social_group_api
 */
function hook_social_group_overview_route_alter(array &$route, GroupInterface $group) {
  if ($group->bundle() == 'challenge') {
    $route = [
      'name' => 'view.challenges_user.page',
      'parameters' => ['user' => \Drupal::currentUser()->id()],
    ];
  }
}

/**
 * Provide a method to return node which was moved to another group.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The event or topic node.
 *
 * @ingroup social_group_api
 */
function hook_social_group_move(\Drupal\node\NodeInterface $node) {
  drupal_set_message(t('@title is moved.', ['@title' => $node->getTitle()]));
}

/**
 * @} End of "addtogroup hooks".
 */

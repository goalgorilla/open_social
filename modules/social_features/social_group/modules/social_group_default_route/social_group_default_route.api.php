<?php

/**
 * @file
 * Hooks provided by the Comment module.
 */

/**
 * @addtogroup hooks
 *
 * @param array $types
 *   An array of all available group types.
 *
 * @deprecated This hook will be excluded, do not use it.
 *
 * @see https://www.drupal.org/node/3487606
 */
function hook_social_group_default_route_types_alter(array $types) {
  // Enable functionality for secret groups.
  $types[] = 'secret_group';
  // Disable functionality for closed groups.
  unset($types['closed_group']);
}

/**
 * Provide group bundles for which entities redirection will be applicable.
 *
 * @return array
 *   An associative array of group bundles that shows if group tab management
 *    should be applied and the default routes for applicable group bundle,
 *    keyed by group bundle. The route should be available as a "Landing Tab"
 *    for the provided group type, otherwise the route will be ignored.
 */
function hook_social_group_default_route_group_types(): array {
  return [
    'flexible_group' => [
      'member' => 'social_group.stream',
      'non-member' => 'view.group_information.page_group_about',
    ],
  ];
}

/**
 * Alter group bundles for which entities redirection will be applicable.
 *
 * @param array $types
 *   An associative array of group bundles and default routes. The route should
 *    be available as a "Landing Tab" for the provided group type, otherwise
 *    the route will be ignored.
 */
function hook_social_group_default_route_group_types_alter(array &$types): void {
  if (isset($types['flexible_group']) && !$types['flexible_group']) {
    $types['flexible_group'] = [
      'member' => 'social_group.stream',
      'non-member' => 'view.group_information.page_group_about',
    ];
  }
}

/**
 * @} End of "addtogroup hooks".
 */

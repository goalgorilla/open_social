<?php

/**
 * @file
 * Hooks provided by the Comment module.
 */

/**
 * Provide group bundles for which entities redirection will be applicable.
 *
 * @return array
 *   An associative array of group bundles that shows if group tab management
 *    should be applied and the default routes for applicable group bundle,
 *    keyed by group bundle.
 */
function hook_social_group_default_route_types(): array {
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
 *   An associative array of group bundles returned by hook_group_types().
 */
function hook_social_group_default_route_types_alter(array &$types): void {
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

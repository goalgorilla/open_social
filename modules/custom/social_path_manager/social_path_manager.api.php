<?php

/**
 * @file
 * Hooks provided by the Social Path Manager module.
 */

/**
 * Hooks to alter the group and tabs included in the automated path aliases.
 *
 * @addtogroupviews hooks
 * @{
 */

/**
 * Provide a method to alter array of group views used in open social.
 *
 * @param array $types
 *   List of group views used in open social.
 *
 * @ingroup social_path_manager_api
 */
function hook_social_path_manager_group_types_alter(array &$types) {
  $types[] = 'challenge';
}

/**
 * Provide a method to alter array of tabs used in open social.
 *
 * @param array $tabs
 *   List of group tabs used in open social.
 *
 * @ingroup social_path_manager_api
 */
function hook_social_path_manager_group_tabs_alter(array &$tabs) {
  $tabs['social_group.about'] = 'about';
}

/**
 * @} End of "addtogroupviews hooks".
 */

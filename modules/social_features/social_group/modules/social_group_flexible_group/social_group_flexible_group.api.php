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
 * Alter an array of routes that require content visibility access checks.
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
 * Provide a description for a given key from the content visibility #options.
 *
 * @param string $description
 *   The descriptive.
 *
 * @ingroup social_group_api
 */
function hook_social_group_flexible_group_allowed_visibility_description_alter($key, &$description) {
  switch ($key) {
    case 'custom_role_1':
      $description = '<p><strong><svg class="icon-small"><use xlink:href="#icon-lock"></use></svg></strong>';
      $description .= '<strong>' . t('Custom role 1')->render() . '</strong>';
      $description .= '-' . t('All users with this role can see it')->render();
      $description .= '</p>';
      break;

    case 'custom_role_2':
      $description = '<p><strong><svg class="icon-small"><use xlink:href="#icon-community"></use></svg></strong>';
      $description .= '<strong>' . t('Custom role 2')->render() . '</strong>';
      $description .= '-' . t('All users with this role can change it')->render();
      $description .= '</p>';
      break;
  }
}

/**
 * Allow for other modules to skip specific views query alter for flexible group.
 *
 * @see social_group_flexible_group_views_query_alter()
 *
 * @return array
 *   List of view ids needs to skip specific query for flexible group.
 *
 * @ingroup social_group_api
 */
function hook_social_flexible_group_skip_query_rules() {
  return [
    'my_personal_open_groups',
  ];
}

/**
 * @} End of "addtogroup hooks".
 */

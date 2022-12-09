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
 * Provide a description for a given key from the flexible group visibility.
 *
 * @param string $key
 *   The visibility option name.
 * @param string $description
 *   An explanation of a visibility option as HTML markup text.
 *
 * @deprecated in social:11.5.0 and is removed from social:12.0.0. Use
 *   hook_social_group_group_visibility_description_alter instead.
 *
 * @see https://www.drupal.org/node/3302921
 *
 * @ingroup social_group_api
 */
function hook_social_group_flexible_group_allowed_visibility_description_alter(string $key, string &$description) {
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
 * @} End of "addtogroup hooks".
 */

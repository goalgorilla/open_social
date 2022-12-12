<?php

/**
 * @file
 * Hooks provided by the Social Event module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a description for a given key from the enroll method #options.
 *
 * @param string $description
 *   The descriptive.
 *
 * @ingroup social_event_api
 */
function hook_social_event_enroll_method_description_alter($key, &$description) {
  switch ($key) {
    case 'join_method_extra':
      $description = '<strong>' . t('QR Code')->render() . '</strong>';
      $description .= '-' . t('All users can join by scanning a QR code')->render();
      $description .= '</p>';
      break;

    case 'single_sign_on':
      $description = '<strong>' . t('Single Sign on')->render() . '</strong>';
      $description .= '-' . t('All users can join by SSO')->render();
      $description .= '</p>';
      break;
  }
}

/**
 * Provide a way to add event menu local tasks to custom pages.
 *
 * @param array $routes
 *   Array of routes where local tasks show show up.
 *
 * @ingroup social_event_api
 */
function hook_social_event_menu_local_tasks_routes_alter(array &$routes) {
  return $routes;
}

/**
 * @} End of "addtogroup hooks".
 */

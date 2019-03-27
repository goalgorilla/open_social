<?php

/**
 * @file
 * Documentation for Social User Export module APIs.
 */

/**
 * Modify the list of available User export plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\social_user_export\UserExportPluginManager
 */
function hook_social_user_export_plugin_info_alter(array &$plugins) {
  if ($plugins['account_name']) {
    unset($plugins['account_name']);
  }
}

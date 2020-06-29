<?php

/**
 * @file
 * Hooks provided by the Social core module.
 */

/**
 * Hooks to alter the visibility of blocks by denying access.
 *
 * @denyaccesstoblock hooks
 * @{
 */

/**
 * Provide a method to alter array of blocks to hide.
 *
 * This way we can also make sure that if another modules alter the same
 * block that it will merge the array in one.
 *
 * @param array $blocks
 *   List of group views used in open social.
 *
 * @ingroup social_core_block_api
 */
function hook_social_core_block_visibility_path_alter(array &$blocks) {
  $changes = [
    'block_plugin_id_1' => [
      'path_1',
      'path_2',
    ],
    'block_plugin_id_2' => [
      'path_1',
      'path_2',
    ]
  ];

  foreach ($changes as $key => $values) {
    foreach ($values as $value) {
      $blocks[$key][] = $value;
    }
  }
}

/**
 * @} End of "denyaccesstoblock hooks".
 */

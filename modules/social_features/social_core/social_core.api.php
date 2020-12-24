<?php

/**
 * @file
 * Hooks provided by the Social core module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the default filter format for a content type.
 *
 * @param string $filter_format
 *   The filter format that is default.
 *
 * @ingroup social_core_api
 */
function hook_social_filter_format_default_alter(&$filter_format) {
  $filter_format = 'full_html';
}

/**
 * @} End of "addtogroup hooks".
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
 * @return array
 *   Blocks in this array will be hidden from the associated paths.
 *
 * @ingroup social_core_block_api
 */
function hook_social_core_block_visibility_path() {
  $blocks = [
    'block_plugin_id_1' => [
      'path_1',
      'path_2',
    ],
    'block_plugin_id_2' => [
      'path_1',
      'path_2',
    ],
  ];

  return $blocks;
}

/**
 * @} End of "denyaccesstoblock hooks".
 */

/**
 * Hooks to alter excluded CT for default title.
 *
 * @hidedefaultitle hooks
 * @{
 */

/**
 * Provide a method to alter array on content types used in open social.
 *
 * @param array $page_to_exclude
 *   Array of content types.
 *
 * @ingroup social_core_api
 */
function hook_social_content_type_alter(array &$page_to_exclude) {
  $page_to_exclude[] = 'article';
}

/**
 * @} End of "hidedefaultitle hooks".
 */

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
 * Provide a method to alter the article for a node. If it's a, or an or the.
 *
 * @param array $node_types
 *   The filter format that is default.
 *
 * @ingroup social_core_api
 */
function hook_social_node_title_prefix_articles_alter(array &$node_types) {
  // The default is set to a.
  // See SocialCoreController::addPageTitle for example.
  $node_types['discussions'] = 'an';
}

/**
 * Provides route for node page where should be displayed simple title.
 *
 * @return string
 *   The route name.
 *
 * @see \Drupal\social_core\Plugin\Block\SocialPageTitleBlock::build()
 */
function hook_social_core_node_default_title_route() {
  return 'entity.node.edit_form';
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
 * Provide method to allows extensions to use the new content style on a node.
 *
 * @param array $compatible_content_type_forms
 *   Array of the nodes.
 *
 * @see social_core_form_node_form_alter()
 * @ingroup social_core_api
 */
function hook_social_core_compatible_content_forms(array &$compatible_content_type_forms) {
  $compatible_content_type_forms[] = 'node_landing_page_form';
}

/**
 * @} End of "hidedefaultitle hooks".
 */

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
 * Provide a method to insert an article in the page title.
 *
 * @return array
 *   An associative array of titles configuration. The keys are entity types.
 *   The values are associative arrays that may contain the following elements:
 *   - route_name: The route name of the page which title should be replaced.
 *   - bundles: (optional) An associative array of articles, keyed by bundle
 *     name.
 *   - callback: (optional) The function should return the config entity object
 *     of an entity type.
 *
 * @see \Drupal\social_core\Routing\RouteSubscriber::alterRoutes()
 * @see \Drupal\social_core\Controller\SocialCoreController::addPageTitle()
 *
 * @ingroup social_core_api
 */
function hook_social_core_title() {
  return [
    'node' => [
      'route_name' => 'node.add',
      'bundles' => [
        'article' => 'an',
      ],
    ],
  ];
}

/**
 * Alter configuration of titles.
 *
 * @param array $titles
 *   An associative array of titles configuration returned by
 *   hook_social_core_title().
 *
 * @see \Drupal\social_core\Routing\RouteSubscriber::alterRoutes()
 * @see \Drupal\social_core\Controller\SocialCoreController::addPageTitle()
 *
 * @ingroup social_core_api
 */
function hook_social_core_title_alter(array &$titles) {
  $titles['node']['bundles']['event'] = 'an';
}

/**
 * Provide a method to alter the article for a node. E.g. 'a', 'an', 'the'.
 *
 * @param array $node_types
 *   The filter format that is default.
 *
 * @deprecated in social:11.4.0 and is removed from social:12.0.0. Use
 *   hook_social_core_title_alter instead.
 *
 * @see https://www.drupal.org/node/3285045
 * @see \Drupal\social_core\Routing\RouteSubscriber::alterRoutes()
 * @see \Drupal\social_core\Controller\SocialCoreController::addPageTitle()
 *
 * @ingroup social_core_api
 */
function hook_social_node_title_prefix_articles_alter(array &$node_types) {
  // The default is set to 'a'.
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
 * Provide a method to alter new content style on an entity.
 *
 * @param array $compatible_content_type_forms
 *   Array of the form identifiers.
 *
 * @see social_core_form_alter()
 * @ingroup social_core_api
 */
function hook_social_core_compatible_content_forms_alter(array &$compatible_content_type_forms) {
  $compatible_content_type_forms[] = 'node_landing_page_form';
}

/**
 * @} End of "hidedefaultitle hooks".
 */

/**
 * Hooks to alter the default main menu links that ships with Open Social.
 *
 * @defaultmainmenulinks hooks
 * @{
 */

/**
 * Provide a method to alter the list of default main menu links.
 *
 * Open Social ships with default main menu links, this links are special and
 * we don't want users to change them, this hooks enables to alter the
 * list of default links.
 *
 * @param array $links
 *   Array of menu_link_content entities.
 *
 * @ingroup social_core_api
 */
function hook_social_core_default_main_menu_links_alter(array &$links) {
}

/**
 * @} End of "defaultmainmenulinks hooks".
 */

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
 * @deprecated in social:13.0.0 and is removed from social:14.0.0. Use
 * hook_social_core_add_form_title_override instead.
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
 * @deprecated in social:13.0.0 and is removed from social:14.0.0. Use
 * hook_social_core_add_form_title_override instead.
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

/**
 * Act on an entity being published.
 *
 * This hook is fired when an entity's status is changed to "published."
 * It works for any entity type that has a `status` field, such as nodes or
 * taxonomy terms. The hook is dynamically invoked based on the entity type.
 * For example:
 * - hook_social_core_node_published() for nodes
 * - hook_social_core_taxonomy_term_published() for taxonomy terms.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being published.
 *
 * @ingroup hooks
 */
function hook_social_core_ENTITY_TYPE_published(\Drupal\Core\Entity\EntityInterface $entity) {
}

/**
 * Act on an entity being unpublished.
 *
 * This hook is fired when an entity's status is changed to "unpublished."
 * It works for any entity type that has a `status` field, such as nodes or
 * taxonomy terms. The hook is dynamically invoked based on the entity type.
 * For example:
 * - hook_social_core_node_unpublished() for nodes
 * - hook_social_core_taxonomy_term_unpublished() for taxonomy terms.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being unpublished.
 *
 * @ingroup hooks
 */
function hook_social_core_ENTITY_TYPE_unpublished(\Drupal\Core\Entity\EntityInterface $entity) {
}

/**
 * Provides a mechanism to override form titles for specific routes dynamically.
 *
 * This hook allows modules to define custom logic for overriding form titles
 * based on the route name and its parameters. The hook implementations should
 * return an array keyed by the route name, with each value containing:
 * - `label`: A string representing the entity type label or a callable function
 *   to dynamically generate the page title. If a callable is provided, it will
 *   receive the route parameters as arguments and must return a string for the
 *   entity type label.
 *
 * Example usage:
 *
 * @code
 * function my_module_social_core_add_form_title_override() {
 *   return [
 *     'entity.node.edit_form' => [
 *       'label' => function ($parameters) {
 *         // Custom logic to generate the label dynamically.
 *         return 'Custom Label for Node';
 *       },
 *     ],
 *     'entity.user.edit_form' => [
 *       'label' => 'User', // Static label.
 *     ],
 *   ];
 * }
 * @endcode
 *
 * @return array
 *   An associative array of overrides keyed by route name. Each key-value pair
 *   includes:
 *   - `label` (string|callable): The label as a string or a callable function
 *     to dynamically generate the label.
 *
 * @see \Drupal\social_core\Routing\RouteSubscriber::alterRoutes()
 * @see \Drupal\social_core\Controller\SocialCoreController::addPageTitle()
 *
 * @ingroup social_core_api
 */
function hook_social_core_add_form_title_override(): array {
  return [];
}

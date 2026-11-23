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
 * Provides the page title for entity add form routes.
 *
 * The title of each route returned by an implementation becomes
 * "Create a <label>" or "Create an <label>". The label is lowercased and the
 * article is chosen by whether the label starts with a vowel, so the label
 * should be the singular name of the thing being created.
 *
 * The hook implementations should return an array keyed by the route name,
 * with each value containing:
 * - `label`: The entity type or bundle label as a string, or a callable that
 *   returns it. A callable is called without arguments when the title is
 *   generated; use the current route match to read the route parameters.
 *
 * Example usage:
 *
 * @code
 * function my_module_social_core_add_form_title_override() {
 *   return [
 *     // Static label: "Create a post".
 *     'entity.post.add_page' => [
 *       'label' => 'Post',
 *     ],
 *     // Dynamic label from the bundle in the route: "Create an article".
 *     'entity.node.add_form' => [
 *       'label' => function () {
 *         $node_type = \Drupal::routeMatch()->getParameter('node_type');
 *         return $node_type?->label() ?? 'Content';
 *       },
 *     ],
 *   ];
 * }
 * @endcode
 *
 * @return array
 *   An associative array of overrides keyed by route name. Each value is an
 *   array with:
 *   - `label` (string|callable): The label as a string, or a callable without
 *     parameters that returns the label.
 *
 * @see \Drupal\social_core\Routing\RouteSubscriber::alterRoutes()
 * @see \Drupal\social_core\Controller\SocialCoreController::generateAddFormTitle()
 *
 * @ingroup social_core_api
 */
function hook_social_core_add_form_title_override(): array {
  return [];
}

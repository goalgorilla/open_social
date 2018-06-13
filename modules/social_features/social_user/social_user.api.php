<?php

/**
 * @file
 * Hooks provided by the Social_user module.
 */

use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows a module to add a link in the account header block.
 *
 * @param array $context
 *   The context that was passed to the header block which can be used to
 *   determine whether to display a menu item.
 *
 * @return array
 *   An associative array of items that should be added in the account header
 *   block. The key of the items should be a unique item name.
 *   Each header item should have the following elements in its array:
 *   - classes
 *   - url
 *   - link_classes
 *   - link_attributes
 *   - title
 *   - icon_image
 *   - icon_classes
 *   - title_classes
 *   - label
 *   - access
 *   - below (iterable or renderable)
 *     - url
 *     ( if URL )
 *     - classes
 *     - link_classes
 *     - link_attributes
 *     - title
 *     - title_classes
 *     - label
 *     - count_icon
 *     - count_classes
 *     ( endif )
 *     - divider
 *     ( elseif divider )
 *     -  classes
 *     ( else )
 *     - classes
 *     - attributes
 *     - tagline
 *     - object
 */
function hook_social_user_account_header_items(array $context) {

}

/**
 * Allows you to alter the results of hook_social_user_account_header_items.
 *
 * @param array $items
 * @param array $context
 *
 * @see hook_social_user_account_header_items.
 */
function hook_social_user_account_header_items_alter(array &$items, array $context) {

}

/**
 * Provide a method to create additional items from the user menu.
 *
 * @return array
 *   An associative array of items from profile section of user menu. The keys
 *   of array elements are keys of menu items.
 *   Each array elements should have the following records:
 *   - title: The text of menu item.
 *   - url: Url object for a URL that has a Drupal route.
 *   - after: Existing element after which will be added new item.
 *   - divider: (optional) "before" for set divider over item and "after" for
 *     set divider under item.
 *
 * @ingroup social_user_api
 */
function hook_social_user_account_header_links() {
  return [
    'logout' => [
      'title' => t('Delete account'),
      'url' => Url::fromRoute('entity.user.cancel_form', [
        'user' => \Drupal::currentUser(),
      ]),
      'after' => 'edit_profile',
      'divider' => 'after',
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */

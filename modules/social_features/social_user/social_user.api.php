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

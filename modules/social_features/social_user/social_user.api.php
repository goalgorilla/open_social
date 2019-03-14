<?php

/**
 * @file
 * Hooks provided by the Social_user module.
 */

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows a module to provide links for the create content menu item.
 *
 * @param array $context
 *   The context that was provided to the block rendering these links.
 *
 * @return array
 *   Items in this array will be used as list items in a dropdown list. All
 *   returned items will be sorted using Element::children.
 *
 * @see \template_preprocess_item_list()
 */
function hook_social_user_account_header_create_links(array $context) {
  // Add a link to create a new page.
  return [
    'add_page' => [
      '#type' => 'link',
      '#attributes' => [
        'title' => new TranslatableMarkup('Create New Page'),
      ],
      '#url' => Url::fromRoute('node.add', ['node_type' => 'page']),
      '#title' => new TranslatableMarkup('New Page'),
      '#weight' => 300,
    ],
  ];
}

/**
 * Allows a module to alter the create content links.
 *
 * @param array $links
 *   The results of all hook_social_user_account_header_create_links functions.
 * @param array $context
 *   The context that was provided to the block rendering these links.
 *
 * @see \hook_social_user_account_header_create_links()
 */
function hook_social_user_account_header_create_links_alter(array &$links, array $context) {
  // Move the "Create New Page" link to the top if it's defined.
  if (isset($links['add_page'])) {
    $links['add_page']['#weight'] = -100;
  }
}

/**
 * Allows a module to provide links for the user menu item.
 *
 * @param array $context
 *   The context that was provided to the block rendering these links.
 *
 * @return array
 *   Items in this array will be used as list items in a dropdown list. All
 *   returned items will be sorted using Element::children.
 *
 * @see \template_preprocess_item_list()
 */
function hook_social_user_account_header_account_links(array $context) {
  // Provides a vertical divider and a logout link for the user.
  return [
    'divider_logout' => [
      "#wrapper_attributes" => [
        "class" => ["divider"],
        "role" => "separator",
      ],
      '#weight' => 1400,
    ],
    'logout' => [
      '#type' => 'link',
      '#attributes' => [
        'title' => new TranslatableMarkup("Logout"),
      ],
      '#url' => Url::fromRoute('user.logout'),
      '#title' => new TranslatableMarkup("Logout"),
      '#weight' => 1500,
    ],
  ];
}

/**
 * Allows a module to alter the user menu item links.
 *
 * @param array $links
 *   The results of all hook_social_user_account_header_account_links functions.
 * @param array $context
 *   The context that was provided to the block rendering these links.
 *
 * @see \hook_social_user_account_header_account_links()
 */
function hook_social_user_account_header_account_links_alter(array &$links, array $context) {
  // Remove the logout divider.
  unset($links['divider_logout']);
}

/**
 * Allows a module to add a link in the account header block.
 *
 * @param array $context
 *   The context that was provided to the block rendering these links.
 *
 * @return array
 *   An associative array of items that should be added in the account header
 *   block. The key of the items should be a unique item name.
 *
 * @see \Drupal\social_user\Element\AccountHeaderElement
 */
function hook_social_user_account_header_items(array $context) {
  // Uses an AccountHeaderElement to easily render a private message shortcut.
  $num_account_messages = 5;
  return [
    'messages' => [
      '#type' => 'account_header_element',
      '#wrapper_attributes' => [
        'class' => ['desktop'],
      ],
      '#title' => new TranslatableMarkup('Inbox'),
      '#url' => Url::fromRoute('social_private_message.inbox'),
      '#icon' => 'icon-message',
      '#label' => (string) $num_account_messages,
    ],
  ];
}

/**
 * Allows you to alter the results of hook_social_user_account_header_items.
 *
 * @param array $items
 *   The results of all hook_social_user_account_header_items functions.
 * @param array $context
 *   The context that was provided to the block rendering these links.
 *
 * @see hook_social_user_account_header_items()
 */
function hook_social_user_account_header_items_alter(array &$items, array $context) {
  // Alter the icon of the private message shortcut.
  if (isset($items['messages'])) {
    $items['messages']['#icon'] = 'icon-envelope';
  }
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
 *
 * @deprecated
 *   This method is replaced by hook_social_user_account_header_account_links
 *   for greater flexibility.
 *
 * @see hook_social_user_account_header_account_links()
 * @see \Drupal\social_user\Plugin\Block\AccountHeaderBlock
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
 * Add suggestions for finding a suitable display name for the user.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account to add display name suggestions for.
 *
 * @return array[]
 *   An array of suggestions with machine name keys. Each suggestion must have
 *   at least a 'name'. The 'name' entry will be displayed to the user. An
 *   optional 'weight' field can be added to the suggestion to control the
 *   priority of the suggestion. 'weight' defaults to 0 if not specified.
 */
function hook_social_user_name_display_suggestions(AccountInterface $account) {
  $suggestions = [];

  // Add a fallback to display when no other suggestions are available.
  $suggestions['fallback'] = [
    'weight' => 1000,
    'name' => 'Name hidden',
  ];

  switch ($account->id()) {
    case 1:
      // Show our admin user the respect she deserves.
      $suggestions['respect_admin'] = [
        'weight' => -1000,
        'name' => 'Jane Almighty',
      ];
      break;
    case 0:
      // Change the name of anonymous users.
      $suggestions['anonymous_alter'] = [
        // Default to weight => 0
        'name' => 'Guest',
      ];
  }

  return $suggestions;
}

/**
 * Alters the list of display suggestions that determine the user display name.
 *
 * @param array $suggestions
 *   The array of suggestion objects generated by
 *   hook_social_user_name_display_suggestions() implementations.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account to modify display name suggestions for.
 */
function hook_social_user_name_display_suggestions_alter(array &$suggestions, AccountInterface $account) {
  // Logged in users are not allowed to view users' full name.
  if (\Drupal::currentUser()->isAuthenticated()) {
    unset($suggestions['full_name']);
  }
}

/**
 * @} End of "addtogroup hooks".
 */

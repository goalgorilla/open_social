<?php

/**
 * @file
 * Post update hooks for the user export extensions.
 */

/**
 * Adds custom action in views.view.user_admin_people after VBO updates.
 *
 * Action ID: 'social_user_export_user_action' action.
 */
function social_user_export_post_update_export_action(): void {
  $config = \Drupal::configFactory()->getEditable('views.view.user_admin_people');
  $selected_actions = $config->get('display.default.display_options.fields.views_bulk_operations_bulk_form.selected_actions');
  // We have to check if action is already added.
  $action = array_search('social_user_export_user_action', array_column($selected_actions, 'action_id'), FALSE);
  // If action is not added we add it.
  if ($action === FALSE) {
    $selected_actions[] = [
      'action_id' => 'social_user_export_user_action',
      'preconfiguration' => [
        'label_override' => '',
      ],
    ];
    $config->set('display.default.display_options.fields.views_bulk_operations_bulk_form.selected_actions', $selected_actions);
    $config->save();
  }
}

<?php

/**
 * @file
 * Contains post update hook implementations.
 */

/**
 * Disable "Get the related groups for this entity".
 *
 * Disable "Get the related groups for this entity" field on "Private Message
 * Author" display for "User" entity if it is enabled.
 */
function social_group_post_update_11101_remove_user_related_groups_for_private_message_author_display(): void {
  $pma_view_display = \Drupal::service('entity_type.manager')
    ->getStorage('entity_view_display')
    ->load('user.user.private_message_author');

  if (!empty($pma_view_display) && $pma_view_display->getComponent('groups')) {
    $pma_view_display->removeComponent('groups')->save();
  }
}

/**
 * Deduplicates selected VBO actions on group manage members view displays.
 */
function social_group_post_update_26001_deduplicate_group_manage_members_selected_actions(): void {
  $config = \Drupal::service('config.factory')->getEditable('views.view.group_manage_members');
  $displays = array_keys((array) $config->get('display'));
  $config_updated = FALSE;

  foreach ($displays as $display_id) {
    $key = "display.$display_id.display_options.fields.social_views_bulk_operations_bulk_form_group.selected_actions";
    $actions = $config->get($key);

    if (!is_array($actions)) {
      continue;
    }

    $normalized_actions = array_values($actions);
    $seen_signatures = [];
    $deduplicated_actions = [];

    foreach ($normalized_actions as $action) {
      if (
        !is_array($action) ||
        !array_key_exists('action_id', $action) ||
        !is_string($action['action_id']) ||
        $action['action_id'] === ''
      ) {
        $deduplicated_actions[] = $action;
        continue;
      }

      $preconfiguration = NULL;
      if (array_key_exists('preconfiguration', $action)) {
        $preconfiguration = $action['preconfiguration'];
      }
      if (array_key_exists('preconfiguration', $action) && !is_array($preconfiguration)) {
        $deduplicated_actions[] = $action;
        continue;
      }

      $label_override = '';
      if (is_array($preconfiguration) && array_key_exists('label_override', $preconfiguration)) {
        if (!is_string($preconfiguration['label_override'])) {
          $deduplicated_actions[] = $action;
          continue;
        }
        $label_override = $preconfiguration['label_override'];
      }
      $signature = "{$action['action_id']}:{$label_override}";
      if (isset($seen_signatures[$signature])) {
        continue;
      }

      $seen_signatures[$signature] = TRUE;
      $deduplicated_actions[] = $action;
    }

    if ($deduplicated_actions !== $actions) {
      $config->set($key, $deduplicated_actions);
      $config_updated = TRUE;
    }
  }

  if ($config_updated) {
    $config->save(TRUE);
  }
}

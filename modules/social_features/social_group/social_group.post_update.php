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

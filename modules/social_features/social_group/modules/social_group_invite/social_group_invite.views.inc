<?php

/**
 * @file
 * Contains Views hooks.
 */

/**
 * Implements hook_views_data().
 */
function social_group_invite_views_data(): array {
  $data = [];

  // Create our own views VBO field for invitations.
  $data['views']['social_views_bulk_operations_bulk_form_invites'] = [
    'title' => t('Social Views bulk operations for invites'),
    'help' => t("Process invites returned by the view with Views Bulk Operations actions."),
    'field' => [
      'id' => 'social_views_bulk_operations_bulk_form_invites',
    ],
  ];

  return $data;
}

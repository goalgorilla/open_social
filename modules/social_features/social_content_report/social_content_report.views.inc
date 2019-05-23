<?php

/**
 * @file
 * Views hooks for Social Content Report.
 */

/**
 * Implements hook_views_data_alter().
 */
function social_content_report_views_data_alter(array &$data) {
  // Close report link.
  $data['flagging']['close_report'] = [
    'title' => t('Link to close report'),
    'field' => [
      'title' => t('Link to close report'),
      'help' => t('Provide a link to close the report'),
      'id' => 'flagging_link_close',
    ],
  ];

  // Report content type.
  $data['flagging']['entity_bundle_type'] = [
    'title' => t('Entity and/or Bundle type'),
    'field' => [
      'title' => t('Entity and/or Bundle type'),
      'help' => t('Displays the entity type or the bundle type for Nodes.'),
      'id' => 'flagging_entity_bundle_type',
    ],
  ];
}

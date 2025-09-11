<?php

/**
 * @file
 * Views hooks for Social Event.
 */

/**
 * Implements hook_views_data().
 */
function social_event_views_data(): array {
  $data['node_field_data']['social_event_is_online'] = [
    'title' => t('Online Event'),
    'filter' => [
      'title' => t('Online Event'),
      'help' => t('Filter events by whether they have an online meeting configured'),
      'group' => t('Social'),
      'field' => 'nid',
      'id' => 'social_event_is_online',
    ],
  ];

  return $data;
}

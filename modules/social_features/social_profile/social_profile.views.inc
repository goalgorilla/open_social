<?php

/**
 * @file
 * Views hooks for Social Profile.
 */

/**
 * Implements hook_views_data_alter().
 */
function social_profile_views_data_alter(array &$data) {
  $data['profile']['profile_entity_sortable'] = [
    'title' => t('Sort by profile entity'),
    'field' => [
      'title' => t('Sort by profile entity'),
      'help' => t('Provide a correct sort alias'),
      'id' => 'profile_entity_sortable',
    ],
  ];
}

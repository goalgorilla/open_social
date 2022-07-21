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

  $data['profile__field_profile_profile_tag']['social_split_profile_terms'] = [
    'title' => t('Filter by profile tags'),
    'filter' => [
      'title' => t('Filter by profile tags'),
      'help' => t('Provide a filter for split profile tags'),
      'group' => t('Profile'),
      'field' => 'field_profile_profile_tag_target_id',
      'id' => 'social_split_profile_terms',
    ],
  ];
}

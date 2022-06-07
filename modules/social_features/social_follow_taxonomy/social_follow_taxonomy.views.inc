<?php

/**
 * @file
 * Contains views API hooks for Social Follow Taxonomy module.
 */

/**
 * Implements hook_views_data().
 */
function social_follow_taxonomy_views_data(): array {
  $data = [];

  $data['users_field_data']['social_follow_taxonomy_follow_filter'] = [
    'title' => t('Taxonomy terms the user is following'),
    'filter' => [
      'title' => t('Taxonomy terms the user is following'),
      'help' => t('Filter users by tags they are following'),
      'id' => 'social_follow_taxonomy_follow_filter',
    ],
  ];

  return $data;
}

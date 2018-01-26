<?php

/**
 * @file
 * Provide views data for social_post module.
 */

/**
 * Implements hook_views_data().
 */
function social_post_views_data() {
  $data = [];
  $data['views']['post_form'] = [
    'title' => t('Sitewide Post form'),
    'help' => t('Adds a sitewide post form.'),
    'area' => [
      'id' => 'social_post_post_form',
    ],
  ];
  return $data;
}

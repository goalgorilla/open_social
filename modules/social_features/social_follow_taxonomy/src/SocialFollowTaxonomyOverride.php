<?php

namespace Drupal\social_follow_taxonomy;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialFollowTaxonomyOverride.
 *
 * Configuration override.
 *
 * @package Drupal\social_follow_taxonomy
 */
class SocialFollowTaxonomyOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Override activity_stream views.
    $config_name = 'views.view.activity_stream';

    if (in_array($config_name, $names)) {
      $data = [
        'id' => 'activity_follow_taxonomy_visibility_access_filter',
        'table' => 'activity',
        'field' => 'activity_follow_taxonomy_visibility_access_filter',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'operator' => '=',
        'value' => '',
        'group' => 1,
        'exposed' => FALSE,
        'expose' => [
          'operator_id' => '',
          'label' => '',
          'description' => '',
          'use_operator' => FALSE,
          'operator' => '',
          'operator_limit_selection' => FALSE,
          'operator_list' => [],
          'identifier' => '',
          'required' => FALSE,
          'remember' => FALSE,
          'multiple' => FALSE,
          'remember_roles' => [
            'authenticated' => 'authenticated',
          ],
        ],
        'is_grouped' => FALSE,
        'group_info' => [
          'label' => '',
          'description' => '',
          'identifier' => '',
          'optional' => TRUE,
          'widget' => 'select',
          'multiple' => FALSE,
          'remember' => FALSE,
          'default_group' => 'All',
          'default_group_multiple' => [],
          'group_items' => [],
        ],
        'entity_type' => 'activity',
        'plugin_id' => 'activity_follow_taxonomy_visibility_access',
      ];

      $displays = [
        'block_stream_homepage',
        'block_stream_homepage_without_post',
      ];

      foreach ($displays as $display) {
        $display_options = &$overrides[$config_name]['display'][$display]['display_options'];
        $display_options['filters']['activity_follow_taxonomy_visibility_access_filter'] = $data;
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialFollowTaxonomyOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}

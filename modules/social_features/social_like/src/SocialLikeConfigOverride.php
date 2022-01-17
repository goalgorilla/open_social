<?php

namespace Drupal\social_like;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialLikeConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_like
 */
class SocialLikeConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Override post photo default.
    $config_names = [
      'core.entity_view_display.post.photo.activity',
      'core.entity_view_display.post.photo.default',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'content' => [
            'like_and_dislike' => [
              'weight' => 2,
              'settings' => [],
              'third_party_settings' => [],
            ],
          ],
        ];
      }
    }

    // Filter votes by translation language.
    $config_name = 'views.view.votingapi_votes';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'node_page' => [
            'display_options' => [
              'filters' => [
                'default_langcode' => [
                  'id' => 'default_langcode',
                  'table' => 'node_field_data',
                  'field' => 'default_langcode',
                  'relationship' => 'entity_id',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'operator' => '=',
                  'value' => '1',
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
                  'entity_type' => 'node',
                  'entity_field' => 'default_langcode',
                  'plugin_id' => 'boolean',
                ],
              ],
            ],
          ],
        ],
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialLikeConfigOverride';
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

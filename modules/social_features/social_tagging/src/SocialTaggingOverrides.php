<?php

namespace Drupal\social_tagging;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 */
class SocialTaggingOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.search_content';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'dependencies' => [
          'config' => [
            'taxonomy.vocabulary.social_tagging',
          ],
        ],
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'social_tagging' => [
                  'id' => 'social_tagging',
                  'table' => 'search_api_index_social_content',
                  'field' => 'social_tagging',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'operator' => 'or',
                  'value' => [],
                  'group' => '1',
                  'exposed' => TRUE,
                  'expose' => [
                    'operator_id' => 'social_tagging_op',
                    'label' => 'Tags',
                    'description' => '',
                    'use_operator' => FALSE,
                    'operator' => 'social_tagging_op',
                    'identifier' => 'tag',
                    'required' => FALSE,
                    'remember' => FALSE,
                    'multiple' => TRUE,
                    'remember_roles' => [
                      'authenticated' => 'authenticated',
                      'anonymous' => '0',
                      'administrator' => '0',
                      'contentmanager' => '0',
                      'sitemanager' => '0',
                    ],
                    'reduce' => FALSE,
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
                  'reduce_duplicates' => FALSE,
                  'type' => 'select',
                  'limit' => TRUE,
                  'vid' => 'social_tagging',
                  'hierarchy' => FALSE,
                  'error_message' => TRUE,
                  'plugin_id' => 'search_api_term',
                ],
              ],
            ],
            'cache_metadata' => [
              'contexts' => [
                'user',
              ],
            ],
          ],
          'page' => [
            'cache_metadata' => [
              'contexts' => [
                'user',
              ],
            ],
          ],
          'page_no_value' => [
            'cache_metadata' => [
              'contexts' => [
                'user',
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'search_api.index.social_content';

    if (in_array($config_name, $names)) {
      $config = \Drupal::service('config.factory')->getEditable($config_name);
      $field_settings = $config->get('field_settings');

      $field_settings['social_tagging'] = [
        'label' => 'Tags',
        'datasource_id' => 'entity:node',
        'property_path' => 'social_tagging',
        'type' => 'integer',
      ];
      $overrides[$config_name]['field_settings'] = $field_settings;
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialUserExportOverrides';
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

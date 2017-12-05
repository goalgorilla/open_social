<?php

namespace Drupal\social_user_export;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 */
class SocialUserExportOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.user_admin_people';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'dependencies' => [
          'module' => [
            'group',
            'social_user_export',
            'user',
          ],
        ],
        'display' => [
          'default' => [
            'display_options' => [
              'group_by' => TRUE,
              'filters' => [
                'id' => [
                  'id' => 'id',
                  'table' => 'groups_field_data',
                  'field' => 'id',
                  'relationship' => 'gid',
                  'admin_label' => '',
                  'operator' => '=',
                  'value' => [
                    'min' => '',
                    'max' => '',
                    'value' => '',
                  ],
                  'group' => 1,
                  'exposed' => TRUE,
                  'expose' => [
                    'operator_id' => 'id_op',
                    'label' => 'Group',
                    'description' => '',
                    'use_operator' => FALSE,
                    'operator' => 'id_op',
                    'identifier' => 'group',
                    'required' => FALSE,
                    'remember' => FALSE,
                    'multiple' => FALSE,
                    'remember_roles' => [
                      'authenticated' => 'authenticated',
                      'anonymoous' => 0,
                      'administrator' => 0,
                      'contentmanager' => 0,
                      'sitemanager' => 0,
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
                  'entity_type' => 'group',
                  'entity_field' => 'id',
                  'plugin_id' => 'numeric',
                ],
              ],
              'relationships' => [
                'group_content' => [
                  'id' => 'group_content',
                  'table' => 'users_field_data',
                  'field' => 'group_content',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => 'User group content',
                  'required' => FALSE,
                  'group_content_plugins' => [
                    'group_membership' => 'group_membership',
                  ],
                  'entity_type' => 'user',
                  'plugin_id' => 'group_content_to_entity_reverse',
                ],
                'gid' => [
                  'id' => 'gid',
                  'table' => 'group_content_field_data',
                  'field' => 'gid',
                  'relationship' => 'group_content',
                  'group_type' => 'group',
                  'admin_label' => 'Group',
                  'required' => FALSE,
                  'entity_type' => 'group_content',
                  'entity_field' => 'gid',
                  'plugin_id' => 'standard',
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

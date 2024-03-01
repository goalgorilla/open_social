<?php

namespace Drupal\social_group_flexible_group;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class SocialGroupSecretConfigOverride.
 *
 * @package Drupal\social_group_secret
 */
class SocialGroupFlexibleGroupConfigOverride implements ConfigFactoryOverrideInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names): array {

    $config_names = [
      'views.view.search_all',
      'views.view.search_groups',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'display' => [
            'default' => [
              'display_options' => [
                'row' => [
                  'options' => [
                    'view_modes' => [
                      'entity:group' => [
                        'flexible_group' => 'teaser',
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_name = 'search_api.index.social_groups';
    // Add join methods as option to search api groups.
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'dependencies' => [
          'config' => [
            'field_storage_group_field_group_allowed_join_method' => 'field.storage.group.field_group_allowed_join_method',
          ],
        ],
        'field_settings' => [
          'field_group_allowed_join_method' => [
            'label' => $this->t('Allowed join method'),
            'datasource_id' => 'entity:group',
            'property_path' => 'field_group_allowed_join_method',
            'type' => 'string',
            'dependencies' => [
              'config' => [
                'field_storage_group_field_group_allowed_join_method' => 'field.storage.group.field_group_allowed_join_method',
              ],
            ],
          ],
        ],
      ];
    }

    // Add search api specific filter for join method.
    $filter_sapi_join_methods = [
      'id' => 'field_group_allowed_join_method',
      'table' => 'search_api_index_social_groups',
      'field' => 'field_group_allowed_join_method',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => 'or',
      'value' => [],
      'group' => 1,
      'exposed' => TRUE,
      'expose' => [
        'operator_id' => 'field_group_allowed_join_method_op',
        'label' => $this->t('Join method'),
        'description' => '',
        'use_operator' => FALSE,
        'operator' => 'field_group_allowed_join_method_op',
        'identifier' => 'field_group_allowed_join_method',
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
        'optional' => TRUE,
        'widget' => 'select',
        'multiple' => FALSE,
        'remember' => FALSE,
        'default_group' => 'All',
        'default_group_multiple' => [],
        'group_items' => [],
      ],
      'plugin_id' => 'search_api_options',
    ];

    $config_name = 'views.view.search_groups';

    if (in_array($config_name, $names)) {
      $overrides[$config_name]['display']['default']['display_options']['filters']['field_group_allowed_join_method'] = $filter_sapi_join_methods;
    }

    return $overrides ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix(): string {
    return 'SocialGroupFlexibleGroupConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name): CacheableMetadata {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): ?StorableConfigBase {
    return NULL;
  }

}

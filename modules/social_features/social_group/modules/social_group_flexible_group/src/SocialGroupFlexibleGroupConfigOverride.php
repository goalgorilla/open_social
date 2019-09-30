<?php

namespace Drupal\social_group_flexible_group;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialGroupSecretConfigOverride.
 *
 * @package Drupal\social_group_secret
 */
class SocialGroupFlexibleGroupConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
  public function loadOverrides($names) {
    $overrides = [];

    $config_names = [
      'search_api.index.social_all',
      'search_api.index.social_groups',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'field_settings' => [
            'rendered_item' => [
              'configuration' => [
                'view_mode' => [
                  'entity:group' => [
                    'flexible_group' => 'teaser',
                  ],
                ],
              ],
            ],
          ],
        ];
      }
    }

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

    $config_names = [
      'views.view.group_members',
      'views.view.group_manage_members',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'dependencies' => [
            'config' => [
              'flexible_group-group_membership' => 'group.content_type.flexible_group-group_membership',
            ],
          ],
          'display' => [
            'default' => [
              'display_options' => [
                'filters' => [
                  'type' => [
                    'value' => [
                      'flexible_group-group_membership' => 'flexible_group-group_membership',
                    ],
                  ],
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_names = [
      'views.view.group_events' => 'flexible_group-group_node-event',
      'views.view.group_topics' => 'flexible_group-group_node-topic',
    ];

    foreach ($config_names as $config_name => $content_type) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'dependencies' => [
            'config' => [
              'group-content-type' => 'group.content_type.' . $content_type,
              'group-type' => 'group.type',
            ],
          ],
          'display' => [
            'default' => [
              'display_options' => [
                'arguments' => [
                  'gid' => [
                    'validate_options' => [
                      'bundles' => [
                        'flexible_group' => 'flexible_group',
                      ],
                    ],
                  ],
                ],
                'filters' => [
                  'type' => [
                    'value' => [
                      $content_type => $content_type,
                    ],
                  ],
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_name = 'views.view.newest_groups';

    $displays = [
      'page_all_groups',
      'block_newest_groups',
    ];

    if (in_array($config_name, $names)) {
      foreach ($displays as $display_name) {
        $overrides[$config_name] = [
          'display' => [
            $display_name => [
              'cache_metadata' => [
                'contexts' => [
                  'user' => 'user',
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_name = 'block.block.views_block__group_managers_block_list_managers';

    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name] = [
        'visibility' => [
          'group_type' => [
            'group_types' => [
              'flexible_group' => 'flexible_group',
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.group_managers';

    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'group_roles_target_id_3' => [
                  'id' => 'group_roles_target_id_3',
                  'table' => 'group_content__group_roles',
                  'field' => 'group_roles_target_id',
                  'relationship' => 'group_content',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'operator' => '=',
                  'value' => 'flexible_group-group_manager',
                  'group' => 1,
                  'exposed' => FALSE,
                  'expose' => [
                    'operator_id' => '',
                    'label' => '',
                    'description' => '',
                    'use_operator' => FALSE,
                    'operator' => '',
                    'identifier' => '',
                    'required' => FALSE,
                    'remember' => FALSE,
                    'multiple' => FALSE,
                    'remember_roles' => [
                      'authenticated' => 'authenticated',
                    ],
                    'placeholder' => '',
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
                  'plugin_id' => 'string',
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
    return 'SocialGroupFlexibleGroupConfigOverride';
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

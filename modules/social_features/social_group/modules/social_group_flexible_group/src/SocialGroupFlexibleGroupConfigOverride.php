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

    // Add Content access views filter to exclude
    // nodes, with visibility group, placed in group you are not a member of.
    $config_names = [
      'views.view.latest_topics' => [
        'default',
        'page_latest_topics',
      ],
      'views.view.upcoming_events' => [
        'default',
        'block_community_events',
        'block_my_upcoming_events',
        'page_community_events',
        'upcoming_events_group',
      ],
    ];

    // Filter plugin for Flexible group node access.
    $filter_node_access = [
      'id' => 'flexible_group_node_access',
      'table' => 'node_access',
      'field' => 'flexible_group_node_access',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => '=',
      'value' => [],
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
      'plugin_id' => 'flexible_group_node_access',
    ];

    foreach ($config_names as $config_name => $displays) {
      if (in_array($config_name, $names)) {
        // Loop through the displays.
        foreach ($displays as $display) {
          $overrides[$config_name]['display'][$display]['display_options']['filters']['flexible_group_node_access'] = $filter_node_access;
        }
      }
    }

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

    $config_name = 'block.block.membershiprequestsnotification';

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

    $config_name = 'block.block.membershiprequestsnotification_2';

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

    $config_names = [
      'message.template.create_content_in_joined_group',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names, FALSE)) {
        $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
          [
            'group_content-flexible_group-group_node-event' => 'group_content-flexible_group-group_node-event',
            'group_content-flexible_group-group_node-topic' => 'group_content-flexible_group-group_node-topic',
          ];
      }
    }
    $config_name = 'message.template.join_to_group';
    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
        [
          'group_content-flexible_group-group_membership' => 'group_content-flexible_group-group_membership',
        ];
    }

    $config_name = 'message.template.invited_to_join_group';
    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
        [
          'group_content-flexible_group-group_invitation' => 'group_content-flexible_group-group_invitation',
        ];
    }

    $config_name = 'message.template.approve_request_join_group';
    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
        [
          'group_content-flexible_group-group_membership' => 'group_content-flexible_group-group_membership',
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

    // Add join options to the all-groups and search groups views.
    $filter_overview_join_methods = [
      'id' => 'field_group_allowed_join_method_value',
      'table' => 'group__field_group_allowed_join_method',
      'field' => 'field_group_allowed_join_method_value',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => 'or',
      'value' => [],
      'group' => 1,
      'exposed' => TRUE,
      'expose' => [
        'operator_id' => 'field_group_allowed_join_method_value_op',
        'label' => 'Join method',
        'description' => '',
        'use_operator' => FALSE,
        'operator' => 'field_group_allowed_join_method_value_op',
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
        'identifier' => 'field_group_allowed_join_method',
        'optional' => TRUE,
        'widget' => 'select',
        'multiple' => FALSE,
        'remember' => FALSE,
        'default_group' => 'All',
        'default_group_multiple' => [],
        'group_items' => [],
      ],
      'plugin_id' => 'list_field',
    ];

    $config_names_groups = [
      'views.view.newest_groups' => [
        'default',
        'page_all_groups',
      ],
    ];

    foreach ($config_names_groups as $config_name_groups => $displays_groups) {
      if (in_array($config_name_groups, $names)) {
        foreach ($displays_groups as $display_group) {
          $overrides[$config_name_groups]['display'][$display_group]['display_options']['filters']['field_group_allowed_join_method_value'] = $filter_overview_join_methods;
        }
      }
    }

    // Add join methods as option to search api groups.
    if (in_array('search_api.index.social_groups', $names)) {
      $overrides['search_api.index.social_groups'] = [
        'dependencies' => [
          'config' => [
            'field_storage_group_field_group_allowed_join_method' => 'field.storage.group.field_group_allowed_join_method',
          ],
        ],
        'field_settings' => [
          'field_group_allowed_join_method' => [
            'label' => 'Allowed join method',
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
        'label' => 'Join method',
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

    if (in_array('views.view.search_groups', $names)) {
      $overrides['views.view.search_groups']['display']['default']['display_options']['filters']['field_group_allowed_join_method'] = $filter_sapi_join_methods;
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

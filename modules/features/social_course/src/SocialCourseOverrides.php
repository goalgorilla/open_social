<?php

namespace Drupal\social_course;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides config overrides in social_course module.
 */
class SocialCourseOverrides implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SocialCourseOverrides constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.upcoming_events';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'upcoming_events_group' => [
            'display_options' => [
              'arguments' => [
                'gid' => [
                  'specify_validation' => TRUE,
                  'validate' => [
                    'type' => 'entity:group',
                  ],
                  'validate_options' => [
                    'access' => '',
                    'bundles' => [
                      'course_advanced' => 'course_advanced',
                    ],
                    'multiple' => FALSE,
                    'operation' => 'view',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.latest_topics';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'group_topics_block' => [
            'display_options' => [
              'arguments' => [
                'gid' => [
                  'specify_validation' => TRUE,
                  'validate' => [
                    'type' => 'entity:group',
                  ],
                  'validate_options' => [
                    'access' => '',
                    'bundles' => [
                      'course_advanced' => 'course_advanced',
                    ],
                    'multiple' => FALSE,
                    'operation' => 'view',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.groups';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'type' => [
                  'id' => 'type',
                  'table' => 'groups_field_data',
                  'field' => 'type',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'entity_type' => 'group',
                  'entity_field' => 'type',
                  'plugin_id' => 'bundle',
                  'operator' => 'not in',
                  'value' => [
                    'course_advanced' => 'course_advanced',
                    'course_basic' => 'course_basic',
                  ],
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
                ],
              ],
            ],
          ],
          'page_user_groups' => [
            'display_options' => [
              'filters' => [
                'type' => [
                  'id' => 'type',
                  'table' => 'groups_field_data',
                  'field' => 'type',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'entity_type' => 'group',
                  'entity_field' => 'type',
                  'plugin_id' => 'bundle',
                  'operator' => 'not in',
                  'value' => [
                    'course_advanced' => 'course_advanced',
                    'course_basic' => 'course_basic',
                  ],
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
                ],
              ],
            ],
          ],
          'block_user_groups' => [
            'display_options' => [
              'filters' => [
                'type' => [
                  'id' => 'type',
                  'table' => 'groups_field_data',
                  'field' => 'type',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'entity_type' => 'group',
                  'entity_field' => 'type',
                  'plugin_id' => 'bundle',
                  'operator' => 'not in',
                  'value' => [
                    'course_advanced' => 'course_advanced',
                    'course_basic' => 'course_basic',
                  ],
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
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_names = [
      'search_api.index.social_all',
      'search_api.index.social_content',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $content_types = [
          'course_article',
          'course_section',
          'course_video',
        ];

        // Alter content type list that needs to be excluded from search.
        $this->moduleHandler->alter('social_course_materials_excluded_from_search', $content_types);
        $overrides[$config_name] = [
          'datasource_settings' => [
            'entity:node' => [
              'bundles' => [
                'selected' => $content_types,
              ],
            ],
          ],
        ];

        if ($config_name === 'search_api.index.social_all') {
          $overrides[$config_name]['field_settings']['group_status'] = [
            'label' => 'Published',
            'datasource_id' => 'entity:group',
            'property_path' => 'status',
            'type' => 'boolean',
          ];
        }
      }
    }

    // Set view mode "Teaser" for "Course" groups in Search All.
    $config_names = [
      'views.view.search_all',
      'views.view.search_groups',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $this->configFactory->getEditable($config_name);
        $bundles = $config->get('display.default.display_options.row.options.view_modes.entity:group');
        $bundles['course_basic'] = 'teaser';
        $bundles['course_advanced'] = 'teaser';

        if (in_array($config_name, $names)) {
          $overrides[$config_name] = [
            'display' => [
              'default' => [
                'display_options' => [
                  'row' => [
                    'options' => [
                      'view_modes' => [
                        'entity:group' => $bundles,
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ];
        }
      }
    }

    $config_name = 'views.view.group_managers';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'group_roles_target_id' => [
                  'operator' => 'ends',
                  'value' => 'group_manager',
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'block.block.views_block__group_managers_block_list_managers';

    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);
      $group_types = $config->get('visibility.group_type.group_types');
      $group_types['course_basic'] = 'course_basic';
      $group_types['course_advanced'] = 'course_advanced';
      $overrides[$config_name] = [
        'visibility' => [
          'group_type' => [
            'group_types' => $group_types,
          ],
        ],
      ];
    }

    // Add Basic and Advanced Courses to related courses field settings.
    $config_names = [
      'field.field.group.course_advanced.field_course_related_courses',
      'field.field.group.course_basic.field_course_related_courses',
    ];

    foreach ($names as $name) {
      if (in_array($name, $config_names)) {
        $overrides[$name] = [
          'settings' => [
            'handler_settings' => [
              'target_bundles' => [
                'course_advanced',
                'course_basic',
              ],
            ],
          ],
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialCourseOverrider';
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
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): ?StorableConfigBase {
    return NULL;
  }

}

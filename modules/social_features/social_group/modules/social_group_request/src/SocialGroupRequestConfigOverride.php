<?php

namespace Drupal\social_group_request;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Social Group request configuration override.
 */
class SocialGroupRequestConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    foreach ($names as $name) {
      if (strpos($name, 'block.block.') === 0) {
        $config = $this->configFactory->getEditable($name);

        if ($config->get('settings.id') == 'local_tasks_block') {
          $visibility_paths = $config->get('visibility');
          if (isset($visibility_paths['request_path']['pages'])) {
            $overrides[$name] = [
              'visibility' => [
                'request_path' => [
                  'pages' => $visibility_paths['request_path']['pages'] . "\r\n/group/*/membership-requests",
                ],
              ],
            ];
          }
        }
      }
    }

    $social_group_types = [
      'open_group',
      'closed_group',
      'public_group',
    ];

    $this->moduleHandler->alter('social_group_types', $social_group_types);

    $default_form_display_configs = [];
    $outsider_role_configs = [];
    foreach ($social_group_types as $social_group_type) {
      $default_form_display_configs[] = "core.entity_form_display.group.{$social_group_type}.default";
      $outsider_role_configs[] = "group.role.{$social_group_type}-outsider";
    }

    foreach ($outsider_role_configs as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $this->configFactory->getEditable($config_name);
        $permissions = $config->get('permissions');
        $permissions[] = 'request group membership';

        $overrides[$config_name] = [
          'permissions' => $permissions,
        ];
      }
    }

    foreach ($default_form_display_configs as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'third_party_settings' => [
            'field_group' => [
              'group_request_membership' => [
                'children' => [
                  'allow_request',
                ],
                'parent_name' => '',
                'weight' => 99,
                'label' => t('Request membership')->render(),
                'format_type' => 'fieldset',
                'format_settings' => [
                  'description' => '',
                  'classes' => '',
                  'id' => '',
                  'required_fields' => FALSE,
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_name = 'field.storage.group.field_group_allowed_join_method';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'settings' => [
          'allowed_values_function' => 'social_group_request_allowed_join_method_values',
        ],
      ];
    }

    $config_name = 'views.view.group_pending_members';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'access' => [
                'type' => 'role',
                'options' => [
                  'role' => [
                    'administrator' => 'administrator',
                  ],
                ]
              ],
            ],
          ],
          'membership_requests' => [
            'display_plugin' => 'page',
            'id' => 'membership_requests',
            'display_title' => 'Membership requests',
            'position' => 2,
            'display_options' => [
              'display_extenders' => [],
              'display_description' => '',
              'path' => 'group/%/membership-requests',
              'title' => 'Membership requests',
              'defaults' => [
                'title' => FALSE,
                'fields' => FALSE,
              ],
              'fields' => [
                'name' => [
                  'id' => 'name',
                  'table' => 'users_field_data',
                  'field' => 'name',
                  'relationship' => 'gc__user',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'label' => 'Member',
                  'exclude' => FALSE,
                  'alter' => [
                    'alter_text' => FALSE,
                    'text' => '',
                    'make_link' => FALSE,
                    'path' => '',
                    'absolute' => FALSE,
                    'external' => FALSE,
                    'replace_spaces' => FALSE,
                    'path_case' => 'none',
                    'trim_whitespace' => FALSE,
                    'alt' => '',
                    'rel' => '',
                    'link_class' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'target' => '',
                    'nl2br' => FALSE,
                    'max_length' => 0,
                    'word_boundary' => TRUE,
                    'ellipsis' => TRUE,
                    'more_link' => FALSE,
                    'more_link_text' => '',
                    'more_link_path' => '',
                    'strip_tags' => FALSE,
                    'trim' => FALSE,
                    'preserve_tags' => '',
                    'html' => FALSE,
                  ],
                  'element_type' => '',
                  'element_class' => '',
                  'element_label_type' => '',
                  'element_label_class' => '',
                  'element_label_colon' => TRUE,
                  'element_wrapper_type' => '',
                  'element_wrapper_class' => '',
                  'element_default_classes' => TRUE,
                  'empty' => '',
                  'hide_empty' => FALSE,
                  'empty_zero' => FALSE,
                  'hide_alter_empty' => TRUE,
                  'click_sort_column' => 'value',
                  'type' => 'user_name',
                  'settings' => [],
                  'group_column' => 'value',
                  'group_columns' => [],
                  'group_rows' => TRUE,
                  'delta_limit' => 0,
                  'delta_offset' => 0,
                  'delta_reversed' => FALSE,
                  'delta_first_last' => FALSE,
                  'multi_type' => 'separator',
                  'separator' => ', ',
                  'field_api_classes' => FALSE,
                  'entity_type' => 'user',
                  'entity_field' => 'name',
                  'plugin_id' => 'field',
                ],
                'field_grequest_message' => [
                  'id' => 'field_grequest_message',
                  'table' => 'group_content__field_grequest_message',
                  'field' => 'field_grequest_message',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'label' => 'Message',
                  'exclude' => FALSE,
                  'alter' => [
                    'alter_text' => FALSE,
                    'text' => '',
                    'make_link' => FALSE,
                    'path' => '',
                    'absolute' => FALSE,
                    'external' => FALSE,
                    'replace_spaces' => FALSE,
                    'path_case' => 'none',
                    'trim_whitespace' => FALSE,
                    'alt' => '',
                    'rel' => '',
                    'link_class' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'target' => '',
                    'nl2br' => FALSE,
                    'max_length' => 0,
                    'word_boundary' => TRUE,
                    'ellipsis' => TRUE,
                    'more_link' => FALSE,
                    'more_link_text' => '',
                    'more_link_path' => '',
                    'strip_tags' => FALSE,
                    'trim' => FALSE,
                    'preserve_tags' => '',
                    'html' => FALSE,
                  ],
                  'element_type' => '',
                  'element_class' => '',
                  'element_label_type' => '',
                  'element_label_class' => '',
                  'element_label_colon' => TRUE,
                  'element_wrapper_type' => '',
                  'element_wrapper_class' => '',
                  'element_default_classes' => TRUE,
                  'empty' => '',
                  'hide_empty' => FALSE,
                  'empty_zero' => FALSE,
                  'hide_alter_empty' => TRUE,
                  'click_sort_column' => 'value',
                  'type' => 'basic_string',
                  'settings' => [],
                  'group_column' => 'value',
                  'group_columns' => [],
                  'group_rows' => TRUE,
                  'delta_limit' => 0,
                  'delta_offset' => 0,
                  'delta_reversed' => FALSE,
                  'delta_first_last' => FALSE,
                  'multi_type' => 'separator',
                  'separator' => ', ',
                  'field_api_classes' => FALSE,
                  'plugin_id' => 'field',
                ],
                'created' => [
                  'id' => 'created',
                  'table' => 'group_content_field_data',
                  'field' => 'created',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'label' => 'Request date',
                  'exclude' => FALSE,
                  'alter' => [
                    'alter_text' => FALSE,
                    'text' => '',
                    'make_link' => FALSE,
                    'path' => '',
                    'absolute' => FALSE,
                    'external' => FALSE,
                    'replace_spaces' => FALSE,
                    'path_case' => 'none',
                    'trim_whitespace' => FALSE,
                    'alt' => '',
                    'rel' => '',
                    'link_class' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'target' => '',
                    'nl2br' => FALSE,
                    'max_length' => 0,
                    'word_boundary' => TRUE,
                    'ellipsis' => TRUE,
                    'more_link' => FALSE,
                    'more_link_text' => '',
                    'more_link_path' => '',
                    'strip_tags' => FALSE,
                    'trim' => FALSE,
                    'preserve_tags' => '',
                    'html' => FALSE,
                  ],
                  'element_type' => '',
                  'element_class' => '',
                  'element_label_type' => '',
                  'element_label_class' => '',
                  'element_label_colon' => TRUE,
                  'element_wrapper_type' => '',
                  'element_wrapper_class' => '',
                  'element_default_classes' => TRUE,
                  'empty' => '',
                  'hide_empty' => FALSE,
                  'empty_zero' => FALSE,
                  'hide_alter_empty' => TRUE,
                  'click_sort_column' => 'value',
                  'type' => 'timestamp',
                  'settings' => [
                    'date_format' => 'social_long_date',
                    'custom_date_format' => '',
                    'timezone' => '',
                  ],
                  'group_column' => 'value',
                  'group_columns' => [],
                  'group_rows' => TRUE,
                  'delta_limit' => 0,
                  'delta_offset' => 0,
                  'delta_reversed' => FALSE,
                  'delta_first_last' => FALSE,
                  'multi_type' => 'separator',
                  'separator' => ', ',
                  'field_api_classes' => FALSE,
                  'entity_type' => 'group_content',
                  'entity_field' => 'created',
                  'plugin_id' => 'field',
                ],
                'gid' => [
                  'id' => 'gid',
                  'table' => 'group_content_field_data',
                  'field' => 'gid',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'label' => 'Parent group',
                  'exclude' => TRUE,
                  'alter' => [
                    'alter_text' => FALSE,
                    'text' => '',
                    'make_link' => FALSE,
                    'path' => '',
                    'absolute' => FALSE,
                    'external' => FALSE,
                    'replace_spaces' => FALSE,
                    'path_case' => 'none',
                    'trim_whitespace' => FALSE,
                    'alt' => '',
                    'rel' => '',
                    'link_class' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'target' => '',
                    'nl2br' => FALSE,
                    'max_length' => 0,
                    'word_boundary' => TRUE,
                    'ellipsis' => TRUE,
                    'more_link' => FALSE,
                    'more_link_text' => '',
                    'more_link_path' => '',
                    'strip_tags' => FALSE,
                    'trim' => FALSE,
                    'preserve_tags' => '',
                    'html' => FALSE,
                  ],
                  'element_type' => '',
                  'element_class' => '',
                  'element_label_type' => '',
                  'element_label_class' => '',
                  'element_label_colon' => FALSE,
                  'element_wrapper_type' => '',
                  'element_wrapper_class' => '',
                  'element_default_classes' => TRUE,
                  'empty' => '',
                  'hide_empty' => FALSE,
                  'empty_zero' => FALSE,
                  'hide_alter_empty' => TRUE,
                  'click_sort_column' => 'target_id',
                  'type' => 'entity_reference_entity_id',
                  'settings' => [],
                  'group_column' => 'target_id',
                  'group_columns' => [],
                  'group_rows' => TRUE,
                  'delta_limit' => 0,
                  'delta_offset' => 0,
                  'delta_reversed' => FALSE,
                  'delta_first_last' => FALSE,
                  'multi_type' => 'separator',
                  'separator' => ', ',
                  'field_api_classes' => FALSE,
                  'entity_type' => 'group_content',
                  'entity_field' => 'gid',
                  'plugin_id' => 'field',
                ],
                'id' => [
                  'id' => 'id',
                  'table' => 'group_content_field_data',
                  'field' => 'id',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'label' => 'ID',
                  'exclude' => TRUE,
                  'alter' => [
                    'alter_text' => FALSE,
                    'text' => '',
                    'make_link' => FALSE,
                    'path' => '',
                    'absolute' => FALSE,
                    'external' => FALSE,
                    'replace_spaces' => FALSE,
                    'path_case' => 'none',
                    'trim_whitespace' => FALSE,
                    'alt' => '',
                    'rel' => '',
                    'link_class' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'target' => '',
                    'nl2br' => FALSE,
                    'max_length' => 0,
                    'word_boundary' => TRUE,
                    'ellipsis' => TRUE,
                    'more_link' => FALSE,
                    'more_link_text' => '',
                    'more_link_path' => '',
                    'strip_tags' => FALSE,
                    'trim' => FALSE,
                    'preserve_tags' => '',
                    'html' => FALSE,
                  ],
                  'element_type' => '',
                  'element_class' => '',
                  'element_label_type' => '',
                  'element_label_class' => '',
                  'element_label_colon' => FALSE,
                  'element_wrapper_type' => '',
                  'element_wrapper_class' => '',
                  'element_default_classes' => TRUE,
                  'empty' => '',
                  'hide_empty' => FALSE,
                  'empty_zero' => FALSE,
                  'hide_alter_empty' => TRUE,
                  'click_sort_column' => 'value',
                  'type' => 'number_integer',
                  'settings' => [
                    'thousand_separator' => '',
                    'prefix_suffix' => FALSE,
                  ],
                  'group_column' => 'value',
                  'group_columns' => [],
                  'group_rows' => TRUE,
                  'delta_limit' => 0,
                  'delta_offset' => 0,
                  'delta_reversed' => FALSE,
                  'delta_first_last' => FALSE,
                  'multi_type' => 'separator',
                  'separator' => ', ',
                  'field_api_classes' => FALSE,
                  'entity_type' => 'group_content',
                  'entity_field' => 'id',
                  'plugin_id' => 'field',
                ],
                'nothing' => [
                  'id' => 'nothing',
                  'table' => 'views',
                  'field' => 'nothing',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'label' => 'Approve membership',
                  'exclude' => TRUE,
                  'alter' => [
                    'alter_text' => TRUE,
                    'text' => 'Approve Membership',
                    'make_link' => TRUE,
                    'path' => 'group/{{ gid }}/content/{{ id }}/approve-membership',
                    'absolute' => FALSE,
                    'external' => FALSE,
                    'replace_spaces' => FALSE,
                    'path_case' => 'none',
                    'trim_whitespace' => FALSE,
                    'alt' => 'Approve membership',
                    'rel' => '',
                    'link_class' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'target' => '',
                    'nl2br' => FALSE,
                    'max_length' => 0,
                    'word_boundary' => TRUE,
                    'ellipsis' => TRUE,
                    'more_link' => FALSE,
                    'more_link_text' => '',
                    'more_link_path' => '',
                    'strip_tags' => FALSE,
                    'trim' => FALSE,
                    'preserve_tags' => '',
                    'html' => FALSE,
                  ],
                  'element_type' => '',
                  'element_class' => '',
                  'element_label_type' => '',
                  'element_label_class' => '',
                  'element_label_colon' => FALSE,
                  'element_wrapper_type' => '',
                  'element_wrapper_class' => '',
                  'element_default_classes' => TRUE,
                  'empty' => '',
                  'hide_empty' => FALSE,
                  'empty_zero' => FALSE,
                  'hide_alter_empty' => FALSE,
                  'plugin_id' => 'custom',
                ],
                'nothing_1' => [
                  'id' => 'nothing_1',
                  'table' => 'views',
                  'field' => 'nothing',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'label' => 'Reject Membership',
                  'exclude' => TRUE,
                  'alter' => [
                    'alter_text' => TRUE,
                    'text' => 'Reject Membership',
                    'make_link' => TRUE,
                    'path' => 'group/{{ gid }}/content/{{ id }}/reject-membership',
                    'absolute' => FALSE,
                    'external' => FALSE,
                    'replace_spaces' => FALSE,
                    'path_case' => 'none',
                    'trim_whitespace' => FALSE,
                    'alt' => 'Reject Membership',
                    'rel' => '',
                    'link_class' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'target' => '',
                    'nl2br' => FALSE,
                    'max_length' => 0,
                    'word_boundary' => TRUE,
                    'ellipsis' => TRUE,
                    'more_link' => FALSE,
                    'more_link_text' => '',
                    'more_link_path' => '',
                    'strip_tags' => FALSE,
                    'trim' => FALSE,
                    'preserve_tags' => '',
                    'html' => FALSE,
                  ],
                  'element_type' => '',
                  'element_class' => '',
                  'element_label_type' => '',
                  'element_label_class' => '',
                  'element_label_colon' => FALSE,
                  'element_wrapper_type' => '',
                  'element_wrapper_class' => '',
                  'element_default_classes' => TRUE,
                  'empty' => '',
                  'hide_empty' => FALSE,
                  'empty_zero' => FALSE,
                  'hide_alter_empty' => FALSE,
                  'plugin_id' => 'custom',
                ],
                'dropbutton' => [
                  'id' => 'dropbutton',
                  'table' => 'views',
                  'field' => 'dropbutton',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'label' => 'Action',
                  'exclude' => FALSE,
                  'alter' => [
                    'alter_text' => FALSE,
                    'text' => '',
                    'make_link' => FALSE,
                    'path' => '',
                    'absolute' => FALSE,
                    'external' => FALSE,
                    'replace_spaces' => FALSE,
                    'path_case' => 'none',
                    'trim_whitespace' => FALSE,
                    'alt' => '',
                    'rel' => '',
                    'link_class' => '',
                    'prefix' => '',
                    'suffix' => '',
                    'target' => '',
                    'nl2br' => FALSE,
                    'max_length' => 0,
                    'word_boundary' => TRUE,
                    'ellipsis' => TRUE,
                    'more_link' => FALSE,
                    'more_link_text' => '',
                    'more_link_path' => '',
                    'strip_tags' => FALSE,
                    'trim' => FALSE,
                    'preserve_tags' => '',
                    'html' => FALSE,
                  ],
                  'element_type' => '',
                  'element_class' => '',
                  'element_label_type' => '',
                  'element_label_class' => '',
                  'element_label_colon' => TRUE,
                  'element_wrapper_type' => '',
                  'element_wrapper_class' => '',
                  'element_default_classes' => TRUE,
                  'empty' => '',
                  'hide_empty' => FALSE,
                  'empty_zero' => FALSE,
                  'hide_alter_empty' => TRUE,
                  'fields' => [
                    'nothing' => 'nothing',
                    'nothing_1' => 'nothing_1',
                    'name' => '0',
                    'created' => '0',
                    'gid' => '0',
                    'id' => '0',
                  ],
                  'destination' => TRUE,
                  'plugin_id' => 'dropbutton',
                ],
              ],
              'access' => [
                'type' => 'group_permission',
                'options' => [
                  'group_permission' => 'administer members',
                ],
              ],
            ],
            'cache_metadata' => [
              'max-age' => -1,
              'contexts' => [
                'languages:language_content',
                'languages:language_interface',
                'url',
                'url.query_args',
                'user.roles',
              ],
              'tags' => [],
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
    return 'SocialGroupRequestConfigOverride';
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

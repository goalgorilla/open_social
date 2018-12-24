<?php

namespace Drupal\social_event_an_enroll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class EventAnEnrollOverride.
 *
 * Override event form.
 *
 * @package Drupal\social_event_an_enroll
 */
class EventAnEnrollOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_factory = \Drupal::service('config.factory');

    $config_names = [
      'core.entity_form_display.node.event.default',
      'views.view.event_manage_enrollments',
    ];

    foreach ($config_names as $config_name) {
      if (!in_array($config_name, $names)) {
        continue;
      }

      $config = $config_factory->getEditable($config_name);

      switch ($config_name) {
        case 'core.entity_form_display.node.event.default':
          $children = $config->get('third_party_settings.field_group.group_event_visibility.children');
          $children[] = 'field_event_an_enroll';

          $content = $config->get('content');
          $content['field_event_an_enroll'] = [
            'weight' => 100,
            'settings' => [
              'display_label' => TRUE,
            ],
            'third_party_settings' => [],
            'type' => 'boolean_checkbox',
            'region' => 'content',
          ];

          $overrides[$config_name] = [
            'third_party_settings' => [
              'field_group' => [
                'group_event_visibility' => [
                  'children' => $children,
                ],
              ],
            ],
            'content' => $content,
          ];

          break;

        case 'views.view.event_manage_enrollments':
          $overrides[$config_name] = [
            'dependencies' => [
              'config' => [
                'field.storage.event_enrollment.field_first_name',
                'field.storage.event_enrollment.field_last_name',
              ],
            ],
            'display' => [
              'default' => [
                'display_options' => [
                  'fields' => [
                    'field_first_name' => [
                      'id' => 'field_first_name',
                      'table' => 'event_enrollment__field_first_name',
                      'field' => 'field_first_name',
                      'relationship' => 'none',
                      'group_type' => 'group',
                      'admin_label' => '',
                      'label' => 'First name',
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
                      'element_label_colon' => TRUE,
                      'element_wrapper_type' => '',
                      'element_wrapper_class' => '',
                      'element_default_classes' => TRUE,
                      'empty' => '',
                      'hide_empty' => FALSE,
                      'empty_zero' => FALSE,
                      'hide_alter_empty' => TRUE,
                      'click_sort_column' => 'value',
                      'type' => 'string',
                      'settings' => [
                        'link_to_entity' => FALSE,
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
                      'plugin_id' => 'field',
                    ],
                    'field_last_name' => [
                      'id' => 'field_last_name',
                      'table' => 'event_enrollment__field_last_name',
                      'field' => 'field_last_name',
                      'relationship' => 'none',
                      'group_type' => 'group',
                      'admin_label' => '',
                      'label' => 'Last name',
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
                      'element_label_colon' => TRUE,
                      'element_wrapper_type' => '',
                      'element_wrapper_class' => '',
                      'element_default_classes' => TRUE,
                      'empty' => '',
                      'hide_empty' => FALSE,
                      'empty_zero' => FALSE,
                      'hide_alter_empty' => TRUE,
                      'click_sort_column' => 'value',
                      'type' => 'string',
                      'settings' => [
                        'link_to_entity' => FALSE,
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
                      'plugin_id' => 'field',
                    ],
                    'rendered_entity' => [
                      'empty' => '{{ field_first_name }} {{ field_last_name }}',
                    ],
                  ],
                  'style' => [
                    'options' => [
                      'columns' => [
                        'field_first_name' => 'field_first_name',
                        'field_last_name' => 'field_last_name',
                      ],
                      'info' => [
                        'field_first_name' => [
                          'sortable' => FALSE,
                          'default_sort_order' => 'asc',
                          'align' => '',
                          'separator' => '',
                          'empty_column' => FALSE,
                          'responsive' => '',
                        ],
                        'field_last_name' => [
                          'sortable' => FALSE,
                          'default_sort_order' => 'asc',
                          'align' => '',
                          'separator' => '',
                          'empty_column' => FALSE,
                          'responsive' => '',
                        ],
                      ],
                    ],
                  ],
                ],
                'cache_metadata' => [
                  'tags' => [
                    'config:field.storage.event_enrollment.field_first_name',
                    'config:field.storage.event_enrollment.field_last_name',
                  ],
                ],
              ],
              'page_manage_enrollments' => [
                'cache_metadata' => [
                  'tags' => [
                    'config:field.storage.event_enrollment.field_first_name',
                    'config:field.storage.event_enrollment.field_last_name',
                  ],
                ],
              ],
            ],
          ];

          break;
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'EventAnEnrollOverride';
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

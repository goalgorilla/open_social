<?php

/**
 * @file
 * Contains \Drupal\social_event_type\SocialEventTypeConfigOverride.
 */

namespace Drupal\social_event_type;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Example configuration override.
 */
class SocialEventTypeConfigOverride implements ConfigFactoryOverrideInterface {

  public function loadOverrides($names) {
    $overrides = array();
    $config_factory = \Drupal::service('config.factory');

    // Override event form display.
    $config_name = 'core.entity_form_display.node.event.default';
    if (in_array($config_name, $names)) {
      $config = $config_factory->getEditable($config_name);

      $children = $config->get('third_party_settings.field_group.group_title_image.children');
      $children[] = 'field_event_type';

      $content = $config->get('content');
      $content['field_event_type'] = [
        'weight' => 1,
        'settings' => [],
        'third_party_settings' => [],
        'type' => 'options_buttons'
      ];

      $overrides[$config_name] = [
        'third_party_settings' => [
          'field_group' => [
            'group_title_image' => [
              'children' => $children
            ]
          ]
        ],
        'content' => $content,
      ];
    }

    // Override event displays.
    $view_modes = [
      'core.entity_view_display.node.event.default',
      'core.entity_view_display.node.event.teaser',
    ];

    foreach ($view_modes as  $config_name) {
      if (in_array($config_name, $names)) {
        $config = $config_factory->getEditable($config_name);

        $content = $config->get('content');
        $content['field_event_type'] = [
          'type' => 'entity_reference_label',
          'weight' => 2,
          'label' => 'hidden',
          'settings' => [
            'link' => FALSE
          ],
          'third_party_settings' => []
        ];

        $overrides[$config_name] = [
          'content' => $content,
        ];

      }
    }

    // Override event views.
    $event_views = [
      'views.view.events' => 'events_overview',
      'views.view.group_events' => 'default',
      'views.view.upcoming_events' => 'page_community_events'
    ];

    foreach ($event_views as $config_name => $display_name) {
      if (in_array($config_name, $names)) {
        $config = $config_factory->getEditable($config_name);

        $filters = $config->get('display.events_overview.display_options.filters');

        $filters['field_event_type_target_id'] = [
          'id' => 'field_event_type_target_id',
          'table' => 'node__field_event_type',
          'field' => 'field_event_type_target_id',
          'relationship' => 'none',
          'group_type' => 'group',
          'admin_label' => '',
          'operator' => 'or',
          'value' => [],
          'group' => 1,
          'exposed' => TRUE,
          'expose' => [
            'operator_id' => 'field_event_type_target_id_op',
            'label' => 'What type of events do you want to see?',
            'description' => '',
            'use_operator' => FALSE,
            'operator' => 'field_event_type_target_id_op',
            'identifier' => 'event_type_id',
            'required' => FALSE,
            'remember' => FALSE,
            'multiple' => FALSE,
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
          'vid' => 'event_types',
          'hierarchy' => FALSE,
          'error_message' => TRUE,
          'plugin_id' => 'taxonomy_index_tid',
        ];

        $overrides[$config_name] = [
          'display' => [
            $display_name => [
              'display_options' => [
                'filters' => $filters
              ]
            ]
          ]
        ];
      }

    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialEventTypeConfigOverride';
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

<?php

namespace Drupal\social_event;

use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation defaults for the event content type.
 *
 * @package Drupal\social_event
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'social_event';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return \Drupal::translation()->translate('Events');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    $views_plugin_content_translation_settings = [
      'display_options' => [
        'rendering_language' => '***LANGUAGE_language_interface***',
      ],
    ];

    $langcode_filter_settings = [
      'id' => 'langcode',
      'table' => 'node_field_data',
      'field' => 'langcode',
      'relationship' => 'field_event',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => 'in',
      'value' => [
        '***LANGUAGE_language_interface***' => '***LANGUAGE_language_interface***',
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
        'operator_list' => [
        ],
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
        'default_group_multiple' => [
        ],
        'group_items' => [
        ],
      ],
      'entity_type' => 'node',
      'entity_field' => 'langcode',
      'plugin_id' => 'language',
    ];

    $default_display_langcode_filter_options = [
      'display' => [
        'default' => [
          'display_options' => [
            'filters' => [
              'langcode' => $langcode_filter_settings,
            ]
          ],
        ],
      ],
    ];

    return [
      'language.content_settings.node.event' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.node.event.title' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.event.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.event.path' => [
        'translatable' => TRUE,
      ],
      'field.field.node.event.body' => [
        'translatable' => TRUE,
      ],
      'field.field.node.event.field_event_location' => [
        'translatable' => TRUE,
      ],
      'field.field.node.event.field_event_address' => [
        'translatable' => TRUE,
      ],
      'field.field.node.event.field_files' => [
        'translatable' => TRUE,
      ],
      'field.field.node.event.field_event_image' => [
        'third_party_settings' => [
          'content_translation' => [
            'translation_sync' => [
              'file' => 'file',
              'alt' => '0',
              'title' => '0',
            ],
          ],
        ],
        'translatable' => TRUE,
      ],
      'views.view.upcoming_events' => [
        'display' => [
          'block_community_events' => $views_plugin_content_translation_settings,
          'block_my_upcoming_events' => $views_plugin_content_translation_settings,
          'page_community_events' => $views_plugin_content_translation_settings,
          'upcoming_events_group' => $views_plugin_content_translation_settings,
        ],
      ],
      'views.view.events' => [
        'display' => [
          'events_overview' => $views_plugin_content_translation_settings,
          'block_events_on_profile' => $views_plugin_content_translation_settings,
        ],
      ],
      'views.view.group_events' => [
        'display' => [
          'page_group_events' => $views_plugin_content_translation_settings,
        ],
      ],
      'views.view.event_manage_enrollments' => $default_display_langcode_filter_options,
      'views.view.user_event_invites' => $default_display_langcode_filter_options,
      'views.view.event_enrollments' => $default_display_langcode_filter_options,
      'views.view.manage_enrollments' => $default_display_langcode_filter_options,
      'views.view.event_manage_enrollment_invites' => $default_display_langcode_filter_options,
      'views.view.event_manage_enrollment_requests' => $default_display_langcode_filter_options,
    ];
  }

}

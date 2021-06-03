<?php

namespace Drupal\social_group_flexible_group;

use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation defaults for the flexible group type.
 *
 * @package Drupal\social_group_flexible_group
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'social_group_flexible_group';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return \Drupal::translation()->translate('Flexible group');
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

    return [
      'language.content_settings.group.flexible_group' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.group.flexible_group.label' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.group.flexible_group.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.group.flexible_group.path' => [
        'translatable' => TRUE,
      ],
      'field.field.group.flexible_group.field_group_description' => [
        'translatable' => TRUE,
      ],
      'field.field.group.flexible_group.field_group_location' => [
        'translatable' => TRUE,
      ],
      'field.field.group.flexible_group.field_group_address' => [
        'translatable' => TRUE,
      ],
      'field.field.group.flexible_group.field_group_image' => [
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
      'views.view.group_information' => [
        'display' => [
          'page_group_about' => $views_plugin_content_translation_settings,
        ],
      ],
    ];
  }

}

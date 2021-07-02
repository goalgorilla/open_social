<?php

namespace Drupal\social_group_flexible_group;

use Drupal\social_content_translation\ContentTranslationConfigOverrideBase;

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
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.group.flexible_group' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.group.flexible_group.menu_link' => [
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
      ],
    ];
  }

}

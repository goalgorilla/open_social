<?php

namespace Drupal\social_group_flexible_group;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation defaults for the flexible group type.
 *
 * @package Drupal\social_group_flexible_group
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  use StringTranslationTrait;

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
    return $this->t('Flexible group');
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

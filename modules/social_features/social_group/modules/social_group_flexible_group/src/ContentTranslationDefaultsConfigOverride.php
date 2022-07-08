<?php

namespace Drupal\social_group_flexible_group;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\social_core\ContentTranslationConfigOverrideBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
  protected function getModule(): string {
    return 'social_group_flexible_group';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName(): TranslatableMarkup {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return $this->t('Flexible group');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides(): array {
    return [
      // Translations for "Flexible Group" group type.
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

      // Translations for "Group Type" vocabulary.
      'language.content_settings.taxonomy_term.group_type' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.taxonomy_term.group_type.name' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.taxonomy_term.group_type.changed' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.taxonomy_term.group_type.description' => [
        'translatable' => TRUE,
      ],
    ];
  }

}

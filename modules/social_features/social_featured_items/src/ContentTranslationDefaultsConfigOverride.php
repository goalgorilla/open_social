<?php

namespace Drupal\social_featured_items;

use Drupal\social_core\ContentTranslationConfigOverrideBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides content translation for the Social Featured Items module.
 *
 * @package Drupal\social_featured_items
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'social_featured_items';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return $this->t('Social Featured Items');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      // Translations for "Featured Items" custom block.
      'language.content_settings.block_content.featured_items' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.block_content.featured_items.info' => [
        'translatable' => TRUE,
      ],
      // Translations for "Featured Item" paragraph type.
      'paragraphs.paragraphs_type.featured_item' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.paragraph.featured_item.status' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.featured_item.field_featured_item_image' => [
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
      // Translations for "Featured Items" paragraph type.
      'paragraphs.paragraphs_type.featured_items' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.paragraph.featured_items.status' => [
        'translatable' => TRUE,
      ],
    ];
  }

}

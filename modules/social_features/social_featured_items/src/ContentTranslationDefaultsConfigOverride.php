<?php

namespace Drupal\social_featured_items;

/**
 * Provides content translation for entities in the Social Featured Items module.
 *
 * @package Drupal\social_featured_items
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
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
    ];
  }

}

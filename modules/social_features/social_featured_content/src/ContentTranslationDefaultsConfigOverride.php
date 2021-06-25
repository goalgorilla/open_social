<?php

namespace Drupal\social_featured_content;

use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation for the Social Featured Content module.
 *
 * @package Drupal\social_featured_content
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.block_content.featured' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.block_content.featured.info' => [
        'translatable' => TRUE,
      ],
    ];
  }

}

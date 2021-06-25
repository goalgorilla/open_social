<?php

namespace Drupal\social_follow_landing_page;

/**
 * Provides content translation for entities in the Social Follow Landing Page module.
 *
 * @package Drupal\social_follow_landing_page
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.block_content.follow_tags' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.block_content.follow_tags.info' => [
        'translatable' => TRUE,
      ],
    ];
  }

}

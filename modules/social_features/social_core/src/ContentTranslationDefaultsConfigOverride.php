<?php

namespace Drupal\social_core;

use Drupal\social_content_translation\ContentTranslationConfigOverrideBase;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides content translation for the Social Core module.
 *
 * @package Drupal\social_core
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'social_core';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return $this->t('Social Core');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      // Translations for "Basic block" custom block.
      'language.content_settings.block_content.basic' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.block_content.basic.info' => [
        'translatable' => TRUE,
      ],
      // Translations for "Hero call to action block" custom block.
      'language.content_settings.block_content.hero_call_to_action_block' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.block_content.hero_call_to_action_block.info' => [
        'translatable' => TRUE,
      ],
      'field.field.block_content.hero_call_to_action_block.field_hero_image' => [
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
      // Translations for "Platform introduction" custom block.
      'language.content_settings.block_content.platform_intro' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.block_content.platform_intro.info' => [
        'translatable' => TRUE,
      ],
    ];
  }

}

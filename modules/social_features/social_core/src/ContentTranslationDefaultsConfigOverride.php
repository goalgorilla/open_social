<?php

namespace Drupal\social_core;

/**
 * Provides content translation for the Social Core module.
 *
 * @package Drupal\social_core
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.block_content.basic' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'language.content_settings.block_content.hero_call_to_action_block' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'language.content_settings.block_content.platform_intro' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.block_content.basic.info' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.block_content.hero_call_to_action_block.info' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.block_content.platform_intro.info' => [
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
    ];
  }

}

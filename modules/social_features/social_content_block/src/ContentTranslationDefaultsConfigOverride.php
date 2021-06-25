<?php

namespace Drupal\social_content_block;

use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation for entities in the Social Content Block module.
 *
 * @package Drupal\social_content_block
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.block_content.custom_content_list' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.block_content.custom_content_list.info' => [
        'translatable' => TRUE,
      ],
    ];
  }

}

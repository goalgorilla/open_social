<?php

namespace Drupal\social_content_block;

use Drupal\social_content_translation\ContentTranslationConfigOverrideBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides content translation for the Social Content Block module.
 *
 * @package Drupal\social_content_block
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'social_content_block';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return $this->t('Social Content Block');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      // Translations for "Custom content list block" custom block.
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

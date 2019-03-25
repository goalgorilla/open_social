<?php

namespace Drupal\social_landing_page;

use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation defaults for the landing page content type.
 *
 * @package Drupal\social_landing_page
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'social_landing_page';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return \Drupal::translation()->translate('Landing Pages');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.node.landing_page' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.node.landing_page.title' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.landing_page.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.landing_page.path' => [
        'translatable' => TRUE,
      ],
      'field.field.node.landing_page.body' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.block.field_block_title' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.button.field_button_link_an' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.button.field_button_link_lu' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.featured.field_featured_description' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.featured.field_featured_title' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.hero.field_hero_image' => [
        'third_party_settings' => [
          'content_translation' => [
            'translation_sync' => [
              'file' => 'file',
              'alt' => '0',
              'title' => '0',
            ],
          ],
        ],
        'translatable' => TRUE,
      ],
      'field.field.paragraph.hero.field_hero_subtitle' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.hero.field_hero_title' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.introduction.field_introduction_text' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.introduction.field_introduction_title' => [
        'translatable' => TRUE,
      ],
    ];
  }

}

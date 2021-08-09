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
      // Translations for "Landing Page" node type.
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
      // Translations for "Section" paragraph type.
      'language.content_settings.paragraph.section' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      // Translations for "Block" paragraph type.
      'language.content_settings.paragraph.block' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      // Translations for "Button" paragraph type.
      'language.content_settings.paragraph.button' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      // Translations for "Hero" paragraph type.
      'language.content_settings.paragraph.hero' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
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
      ],
      // Translations for "Hero small" paragraph type.
      'language.content_settings.paragraph.hero_small' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'field.field.paragraph.hero_small.field_hero_small_image' => [
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
      // Translations for "Introduction" paragraph type.
      'language.content_settings.paragraph.introduction' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      // Translations for "Accordion" paragraph type.
      'language.content_settings.paragraph.accordion' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      // Translations for "Accordion Item" paragraph type.
      'language.content_settings.paragraph.accordion_item' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
    ];
  }

}

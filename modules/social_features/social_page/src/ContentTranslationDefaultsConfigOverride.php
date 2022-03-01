<?php

namespace Drupal\social_page;

use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation defaults for the basic page content type.
 *
 * @package Drupal\social_page
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected TranslationManager $translationManager;

  /**
   * Constructs for ContentTranslationDefaultsConfigOverride class.
   *
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation_manager
   *   The string translation service.
   */
  public function __construct(TranslationManager $translation_manager) {
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'social_page';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return $this->translationManager->translate('Pages');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.node.page' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.node.page.title' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.page.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.page.path' => [
        'translatable' => TRUE,
      ],
      'field.field.node.page.body' => [
        'translatable' => TRUE,
      ],
      'field.field.node.page.field_page_image' => [
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
    ];
  }

}

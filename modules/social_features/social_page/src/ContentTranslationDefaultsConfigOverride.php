<?php

namespace Drupal\social_page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation defaults for the basic page content type.
 *
 * @package Drupal\social_page
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {
  use StringTranslationTrait;

  /**
   * Creates a ContentTranslationDefaultsConfigOverride instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
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
    return $this->t('Pages');
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

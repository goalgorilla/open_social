<?php

namespace Drupal\social_book;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation defaults for the book content type.
 *
 * @package Drupal\social_book
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModule() {
    return 'social_book';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
    return $this->t('Book Pages');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.node.book' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.node.book.title' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.book.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.book.path' => [
        'translatable' => TRUE,
      ],
      'field.field.node.book.body' => [
        'translatable' => TRUE,
      ],
      'field.field.node.book.field_book_image' => [
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

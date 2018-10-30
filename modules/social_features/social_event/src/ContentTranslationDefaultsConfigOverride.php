<?php

namespace Drupal\social_event;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\social_core\ContentTranslationConfigOverrideBase;

/**
 * Provides content translation defaults for the event content type.
 *
 * @package Drupal\social_event
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
    return 'social_event';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName() {
    return $this->t('Events');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.node.event' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.node.event.title' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.event.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.event.path' => [
        'translatable' => TRUE,
      ],
      'field.field.node.event.body' => [
        'translatable' => TRUE,
      ],
      'field.field.node.event.field_event_image' => [
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

<?php

namespace Drupal\social_event;

use Drupal\social_core\ContentTranslationConfigOverrideBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides content translation defaults for the event content type.
 *
 * @package Drupal\social_event
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  use StringTranslationTrait;

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
    // We can't use dependency injection here because it causes a circular
    // dependency for the configuration override.
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
      ],
    ];
  }

}

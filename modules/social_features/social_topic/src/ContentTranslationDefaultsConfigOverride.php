<?php

namespace Drupal\social_topic;

use Drupal\social_core\ContentTranslationConfigOverrideBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides content translation defaults for the event content type.
 *
 * @package Drupal\social_topic
 */
class ContentTranslationDefaultsConfigOverride extends ContentTranslationConfigOverrideBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModule():string {
    return 'social_topic';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayName(): string {
    return $this->t('Topics');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationOverrides():array {
    return [
      'language.content_settings.node.topic' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.node.topic.title' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.topic.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.topic.path' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.topic.uid' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.topic.status' => [
        'translatable' => TRUE,
      ],
    ];
  }

}

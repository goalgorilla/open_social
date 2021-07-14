<?php

namespace Drupal\social_topic;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides content translation defaults for the event content type.
 *
 * @package Drupal\social_topic
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // If the module "content_translation" is enabled let make translations
    // enabled for content provided by the module by default.
    $is_content_translations_enabled = \Drupal::moduleHandler()
      ->moduleExists('content_translation');

    if (!$is_content_translations_enabled) {
      return $overrides;
    }

    // Translations for "Topic" node type.
    $config_name = 'language.content_settings.node.topic';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }
    $config_names = [
      'core.base_field_override.node.topic.title',
      'core.base_field_override.node.topic.menu_link',
      'core.base_field_override.node.topic.path',
      'core.base_field_override.node.topic.uid',
      'core.base_field_override.node.topic.status',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'translatable' => TRUE,
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'social_topic.content_translation_defaults_config_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}

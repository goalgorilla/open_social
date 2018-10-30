<?php

namespace Drupal\social_event;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides content translation defaults for the event content type.
 *
 * @package Drupal\social_event
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // This setting can't be changed in an override because that would create
    // and endless loop in trying to apply the override.
    $settings = \Drupal::configFactory()->getEditable('social_content_translation.settings');
    $translate_event = $settings->getOriginal('social_event', FALSE);

    // If the social_content_translation settings object doesn't exist or we are
    // disabled then we perform no overrides.
    if ($translate_event) {
      $this->addTranslationOverrides($names, $overrides);
    }

    return $overrides;
  }

  /**
   * Adds the overrides for this config overrides for translations.
   *
   * By making this a separate method it can easily be overwritten in child
   * classes without having to duplicate the logic of whether it should be
   * invoked.
   *
   * @param array $names
   *   The names of the configuration keys for which overwrites are requested.
   * @param array $overrides
   *   The array of overrides that should be adjusted.
   */
  protected function addTranslationOverrides(array $names, array &$overrides) {
    $translation_overrides = [
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

    foreach ($translation_overrides as $name => $override) {
      if (in_array($name, $names)) {
        $overrides[$name] = $override;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return __CLASS__;
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

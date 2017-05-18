<?php

/**
 * @file
 * Contains \Drupal\social_post_photo\SocialPostPhotoConfigOverride.
 */

namespace Drupal\social_post_photo;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Example configuration override.
 */
class SocialPostPhotoConfigOverride implements ConfigFactoryOverrideInterface {


  /**
   * Returns config overrides.
   *
   * @param array $names
   *   A list of configuration names that are being loaded.
   *
   * @return array
   *   An array keyed by configuration name of override data. Override data
   *   contains a nested array structure of overrides.
   */
  public function loadOverrides($names) {
    $overrides = array();
    $config_factory = \Drupal::service('config.factory');

    // Override event form display.
    $config_name = 'message.template.create_post_community';
    if (in_array($config_name, $names)) {
      $config = $config_factory->getEditable($config_name);

      $entities = $config->get('third_party_settings.activity_logger.activity_bundle_entities');
      $entities['post-photo'] = 'post-photo';

      $overrides[$config_name] = [
        'third_party_settings' => [
          'activity_logger' => [
            'activity_bundle_entities' => $entities
          ]
        ],
      ];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialPostPhotoConfigOverride';
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

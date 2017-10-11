<?php

namespace Drupal\social_like;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialLikeConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_like
 */
class SocialLikeConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_factory = \Drupal::service('config.factory');

    // Override post photo default.
    $config_names = [
      'core.entity_view_display.post.photo.activity',
      'core.entity_view_display.post.photo.default',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $config_factory->getEditable($config_name);
        $content = $config->get('content');

        $content['like_and_dislike'] = [
          'weight' => 2,
          'settings' => [],
          'third_party_settings' => [],
        ];

        $overrides[$config_name] = [
          'content' => $content,
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialLikeConfigOverride';
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

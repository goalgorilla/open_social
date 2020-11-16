<?php

namespace Drupal\social_post_album;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialPostPhotoAlbumConfigOverride.
 *
 * @package Drupal\social_post_album
 */
class SocialPostPhotoAlbumConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Field widget or formatter type per configuration.
   */
  const TYPES = [
    'core.entity_form_display.post.photo.default' => 'social_post_album_image',
    'core.entity_view_display.post.photo.activity' => 'album_image',
  ];

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    foreach (self::TYPES as $config_name => $type) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name]['content']['field_post_image']['type'] = $type;
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialPostPhotoAlbumConfigOverride';
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

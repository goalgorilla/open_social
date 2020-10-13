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
   * Returns config overrides.
   *
   * @param array $names
   *   A list of configuration names that are being loaded.
   *
   * @return array
   *   An array keyed by configuration name of override data. Override data
   *   contains a nested array structure of overrides.
   * @codingStandardsIgnoreStart
   */
  public function loadOverrides($names) {
    // @codingStandardsIgnoreEnd
    $overrides = [];

    $config_name = 'core.entity_form_display.post.photo.group';

    if (in_array($config_name, $names)) {
      $overrides[$config_name]['content']['field_post_image']['settings']['preview_image_style'] = 'social_x_large';
    }

    $config_name = 'core.entity_view_display.post.photo.activity';

    if (in_array($config_name, $names)) {
      $overrides[$config_name]['content']['field_post_image']['type'] = 'album_image';
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

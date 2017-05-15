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
    // TODO: Implement loadOverrides() method.
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

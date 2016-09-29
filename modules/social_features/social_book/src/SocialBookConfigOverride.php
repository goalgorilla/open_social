<?php

/**
 * @file
 * Contains \Drupal\social_book\SocialBookConfigOverride.
 */

namespace Drupal\social_book;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Example configuration override.
 */
class SocialBookConfigOverride implements ConfigFactoryOverrideInterface {

  public function loadOverrides($names) {
    $overrides = array();
    // Set hero title block for book content type.
    $config_name = 'block.block.socialbase_pagetitleblock';
    if (in_array($config_name, $names)) {
      $config = \Drupal::service('config.factory')->getEditable($config_name);
      $bundles = $config->get('visibility.node_type.bundles');
      $bundles['book'] = 'book';
      $overrides[$config_name] = ['visibility' => ['node_type' => ['bundles' => $bundles]]];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialBookConfigOverride';
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
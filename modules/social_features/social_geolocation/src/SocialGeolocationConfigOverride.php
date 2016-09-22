<?php
/**
 * @file
 * Contains \Drupal\social_geolocation\SocialGeolocationConfigOverride.
 */
namespace Drupal\social_geolocation;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Example configuration override.
 */
class SocialGeolocationConfigOverride implements ConfigFactoryOverrideInterface {
  public function loadOverrides($names) {
    $overrides = array();

    // Set download count widget to files fields.
    $search_views = ['search_groups', 'search_content', 'search_users'];
    foreach ($search_views as $content_type) {
      $config_name = "views.view.{$content_type}";
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = ['status' => FALSE];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialGeolocationConfigOverride';
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

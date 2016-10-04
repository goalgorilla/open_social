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

    // Disable Social Search Views.
    $search_views = ['search_groups', 'search_content', 'search_users'];
    foreach ($search_views as $content_type) {
      $config_name = "views.view.{$content_type}";
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = ['status' => FALSE];
      }
    }

    // Disable Social Search Blocks.
    $search_blocks = [
      'search_block_hero',
      'search_content_block_header',
      'exposed_form_search_content_page_sidebar',
      'exposed_form_search_users_page_sidebar',
      'search_users'
    ];
    foreach ($search_blocks as $search_block) {
      $config_name = "block.block.{$search_block}";
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

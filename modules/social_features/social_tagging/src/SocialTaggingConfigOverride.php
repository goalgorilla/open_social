<?php

namespace Drupal\social_tagging;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Class SocialTaggingConfigOverride.
 *
 * Configuration override.
 *
 * @package Drupal\social_tagging
 */
class SocialTaggingConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   *
   * @param array $names
   *   An array of config names.
   *
   * @return array
   *   An array of overridden config.
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Set hero title block for book content type.
    $config_names = [
      'search_api.index.social_content',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = \Drupal::service('config.factory')->getEditable($config_name);
        $fields = $config->get('field_settings');

        if (!isset($fields['social_tagging'])) {
          $overrides[$config_name]['field_settings']['social_tagging'] = [
            "label" => "Tagging",
            "datasource_id" => "entity:node",
            "property_path" => "social_tagging",
            "type" => "integer",
          ];
        }
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialLandingPageConfigOverride';
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

<?php

namespace Drupal\social_landing_page;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Class SocialLandingPageConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_landing_page
 */
class SocialLandingPageConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Set hero title block for book content type.
    $config_names = [
      'search_api.index.social_all',
      'search_api.index.social_content',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'datasource_settings' => [
            'entity:node' => [
              'bundles' => [
                'selected' => [
                  'landing_page' => 'landing_page',
                ],
              ],
            ],
          ],
        ];
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

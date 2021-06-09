<?php

namespace Drupal\social_profile_privacy;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Adds the Restricted Name field to our required processors.
 *
 * @package Drupal\social_profile_privacy
 */
class RestrictedNameProcessorOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Set processor settings for social all and users.
    $config_names = [
      'search_api.index.social_all',
      'search_api.index.social_users',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names, TRUE)) {
        $overrides[$config_name] = [
          'processor_settings' => [
            'tokenizer' => [
              'fields' => [
                'social_profile_privacy_restricted_name' => 'social_profile_privacy_restricted_name',
              ],
            ],
            'ignorecase' => [
              'fields' => [
                'social_profile_privacy_restricted_name' => 'social_profile_privacy_restricted_name',
              ],
            ],
            'transliteration' => [
              'fields' => [
                'social_profile_privacy_restricted_name' => 'social_profile_privacy_restricted_name',
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
    return 'SocialProfileProviacy';
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

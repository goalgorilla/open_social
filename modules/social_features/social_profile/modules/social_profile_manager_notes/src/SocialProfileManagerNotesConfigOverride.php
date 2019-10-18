<?php

namespace Drupal\social_profile_manager_notes;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialProfileManagerNotesConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_profile_manager_notes
 */
class SocialProfileManagerNotesConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'core.entity_form_display.profile.profile.default';
    if (in_array($config_name, $names)) {
      // Add the manager note field to the profile.
      $overrides[$config_name]['content']['field_note'] = [
        'weight' => 6,
        'settings' => [
          'view_mode' => 'default',
          'placeholder' => '',
        ],
        'third_party_settings' => [],
        'type' => 'comment_default',
        'region' => 'content',
        'label' => 'visually_hidden',
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialProfileManagerNotesConfigOverride';
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


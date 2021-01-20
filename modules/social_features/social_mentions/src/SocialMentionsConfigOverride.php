<?php

namespace Drupal\social_mentions;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialMentionsConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_mentions
 */
class SocialMentionsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Add mentions filter to Basic HTML text format.
    $config_name = 'filter.format.basic_html';
    if (in_array($config_name, $names)) {
      $config = \Drupal::service('config.factory')->getEditable($config_name);
      $dependencies = $config->getOriginal('dependencies.module');
      $overrides[$config_name]['dependencies']['module'] = $dependencies;
      $overrides[$config_name]['dependencies']['module'][] = 'mentions';

      $overrides[$config_name]['filters']['filter_mentions'] = [
        'id' => 'filter_mentions',
        'provider' => 'mentions',
        'status' => TRUE,
        'weight' => 40,
        'settings' => [
          'mentions_filter' => [
            'ProfileMention' => 1,
            'UserMention' => 1,
          ],
        ],
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialMentionsConfigOverride';
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

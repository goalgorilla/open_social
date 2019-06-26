<?php

namespace Drupal\social_post;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialPostConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_post
 */
class SocialPostConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_factory = \Drupal::service('config.factory');

    // Override comments order.
    $config_names = [
      'core.entity_view_display.post.post.default',
      'core.entity_view_display.post.post.activity',
    ];

    foreach ($config_names as $config_name) {
     if (in_array($config_name, $names)) {
       $config = $config_factory->getEditable($config_name);
       $comments_order = $config->get('content.field_post_comments.settings.order');
       $overrides[$config_name]["content"]["field_post_comments"]["settings"]["order"] = $comments_order;
     }
    }

    return $overrides;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialPostConfigOverride';
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

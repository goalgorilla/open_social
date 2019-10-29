<?php

namespace Drupal\social_poll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Class SocialPollConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_landing_page
 */
class SocialPollConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_names = [
      'field.field.paragraph.section.field_section_paragraph',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {

        $x=-1;
//        $config = \Drupal::service('config.factory')->getEditable($config_name);
//        $bundles = $config->get('datasource_settings.entity:node.bundles.selected');
//        $bundles[] = 'landing_page';
//        $overrides[$config_name] = ['datasource_settings' => ['entity:node' => ['bundles' => ['selected' => $bundles]]]];
      }
    }



    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialPollConfigOverride';
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

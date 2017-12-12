<?php

namespace Drupal\social_group;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialGroupSelectorWidgetConfigOverride.
 *
 * @package Drupal\social_group
 */
class SocialGroupSelectorWidgetConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_names = [
      'core.entity_form_display.node.event.default',
      'core.entity_form_display.node.topic.default',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = \Drupal::service('config.factory')->getEditable($config_name);
        // Add the field to the content.
        $content = $config->get('content');
        $content['groups'] = [];
        $content['groups']['type'] = 'social_group_selector_widget';
        $content['groups']['settings'] = [];
        $content['groups']['weight'] = 16;
        $content['groups']['region'] = 'content';
        $content['groups']['third_party_settings'] = [];

        $overrides[$config_name] = [
          'content' => $content,
        ];

      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialGroupSelectorWidgetConfigOverride';
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

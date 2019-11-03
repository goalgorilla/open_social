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
    $config_factory = \Drupal::service('config.factory');

    $config_names = [
      'field.field.paragraph.section.field_section_paragraph',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $config_factory->getEditable($config_name);

        // Add our field as config dependency.
        $config_dependencies = $config->get('dependencies.config');
        $config_dependencies_next_index = count($config_dependencies);
        $overrides[$config_name]['dependencies']['config'][$config_dependencies_next_index] = 'paragraphs.paragraphs_type.poll_item';

        // Add our field itself to the index.
        $overrides[$config_name] = [
          'settings' => [
            'handler_settings' => [
              'target_bundles_drag_drop' => [
                'poll_item' => [
                  'enabled' => TRUE,
                  'weight' => 9,
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

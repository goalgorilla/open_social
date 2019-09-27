<?php

namespace Drupal\social_landing_page;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

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
        $config = \Drupal::service('config.factory')->getEditable($config_name);
        $bundles = $config->get('datasource_settings.entity:node.bundles.selected');
        $bundles[] = 'landing_page';
        $overrides[$config_name] = ['datasource_settings' => ['entity:node' => ['bundles' => ['selected' => $bundles]]]];
      }
    }

    $config_names = [
      'core.entity_form_display.paragraph.block.default',
      'core.entity_form_display.paragraph.featured.default',
      'core.entity_form_display.paragraph.featured_item.default',
      'core.entity_form_display.paragraph.hero.default',
      'core.entity_form_display.paragraph.hero_small.default',
      'core.entity_form_display.paragraph.introduction.default',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        // Grab current configuration and push the new values.
        $config = $this->configFactory->getEditable($config_name);
        // We have to add config dependencies to field storage.
        $dependencies = $config->getOriginal('dependencies', FALSE)['config'];
        $dependencies[] = 'field.field.paragraph.field_roles';
        $overrides[$config_name]['dependencies']['config'] = $dependencies;
        $overrides[$config_name]['content']['field_roles'] = [
          'region' => 'content',
          'type' => 'options_select',
          'weight' => 5,
          'third_party_settings' => [],
          'settings' => [],
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

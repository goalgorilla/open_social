<?php

namespace Drupal\social_book;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialBookConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_book
 */
class SocialBookConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SocialBookConfigOverride constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration Factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Set hero title block for book content type.
    $config_names = [
      'block.block.socialbase_pagetitleblock',
      'block.block.socialblue_pagetitleblock',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $this->configFactory->getEditable($config_name);
        $bundles = $config->get('visibility.node_type.bundles');
        $bundles['book'] = 'book';
        $overrides[$config_name] = ['visibility' => ['node_type' => ['bundles' => $bundles]]];
      }
    }

    // Ensure book pages are added to social_all so search all
    // and search autocomplete index and show book results correctly.
    $config_names = [
      'search_api.index.social_all',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name]['field_settings']['rendered_item']['configuration']['view_mode']['book'] = 'search_index';
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialBookConfigOverride';
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

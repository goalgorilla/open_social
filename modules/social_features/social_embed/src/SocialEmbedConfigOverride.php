<?php

namespace Drupal\social_embed;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialEmbedConfigOverride.
 *
 * @package Drupal\social_embed
 */
class SocialEmbedConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_names = [
      'filter.format.basic_html',
      'filter.format.full_html',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        /* @var \Drupal\Core\Config\ConfigFactory $config */
        $config = \Drupal::service('config.factory')->getEditable($config_name);

        $dependencies = $config->get('dependencies.module');
        $dependencies[] = 'url_embed';

        $filters = $config->get('filters');
        $filters['url_embed'] = [
          'id' => 'url_embed',
          'provider' => 'url_embed',
          'status' => TRUE,
          'weight' => 100,
          'settings' => [],
        ];
        if ($config_name === 'filter.format.basic_html') {
          $filters['social_embed_convert_url'] = [
            'id' => 'social_embed_convert_url',
            'provider' => 'social_embed',
            'status' => TRUE,
            'weight' => (isset($filters['filter_url']['weight']) ? $filters['filter_url']['weight'] - 1 : $filters['url_embed']['weight'] - 1),
            'settings' => [
              'url_prefix' => '',
            ],
          ];
          if (isset($filters['filter_html'])) {
            $filters['filter_html']['settings']['allowed_html'] .= ' <drupal-url data-*>';
          }
        }

        $overrides[$config_name] = [
          'dependencies' => [
            'module' => $dependencies,
          ],
          'filters' => $filters,
        ];
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialEmbedConfigOverride';
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

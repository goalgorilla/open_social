<?php

namespace Drupal\social_embed;

use Drupal\Core\Config\Config;

/**
 * Class SocialEmbedConfigOverride.
 *
 * @package Drupal\social_embed
 */
class SocialEmbedConfigOverride extends SocialEmbedConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  public function doOverride(Config $config, $config_name, $convert_url, array &$overrides) {
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

    if ($convert_url) {
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

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialEmbedConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    return 'filter.format';
  }

}

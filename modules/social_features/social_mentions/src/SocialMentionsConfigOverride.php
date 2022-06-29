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

    $formats = [
      'basic_html',
      'simple_html',
    ];

    foreach ($formats as $format) {
      if (in_array('filter.format.' . $format, $names)) {
        $this->addFilterOverride($format, $overrides);
      }
    }

    return $overrides;
  }

  /**
   * Alters the filter settings for the text format.
   *
   * @param string $text_format
   *   A config name.
   * @param array $overrides
   *   An override configuration.
   */
  protected function addFilterOverride($text_format, array &$overrides): void {
    $config_name = 'filter.format.' . $text_format;
    /** @var \Drupal\Core\Config\Config $config */
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

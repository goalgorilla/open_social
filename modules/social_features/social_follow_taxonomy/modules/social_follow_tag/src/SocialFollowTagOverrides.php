<?php

namespace Drupal\social_follow_tag;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialFollowTagOverrides.
 *
 * Override section paragraph settings.
 *
 * @package Drupal\social_follow_tag
 */
class SocialFollowTagOverrides implements ConfigFactoryOverrideInterface {

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
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Add tag paragraph to bundle list in section paragraph config.
    $config_name = 'field.field.paragraph.section.field_section_paragraph';

    if (in_array($config_name, $names)) {
      $overrides[$config_name]['settings']['handler_settings']['target_bundles']['tag'] = 'tag';
      $overrides[$config_name]['settings']['handler_settings']['target_bundles_drag_drop']['tag']['enabled'] = TRUE;
    }

    // Add social_tagging taxonomy bundle to follow_term flag config.
    $config_name = 'flag.flag.follow_term';
    $config = \Drupal::service('config.factory')->getEditable($config_name);
    $bundles = $config->get('bundles');
    if (!empty($bundles) && !in_array('social_tagging', $bundles)) {
      array_push($bundles, 'social_tagging');

      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'bundles' => $bundles,
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialFollowTagOverrides';
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

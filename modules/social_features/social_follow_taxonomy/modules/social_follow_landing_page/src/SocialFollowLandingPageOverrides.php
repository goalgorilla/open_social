<?php

namespace Drupal\social_follow_landing_page;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialFollowLandingPageOverrides.
 *
 * Override section paragraph settings.
 *
 * @package Drupal\social_follow_landing_page
 */
class SocialFollowLandingPageOverrides implements ConfigFactoryOverrideInterface {

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

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialFollowLandingPageOverrides';
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

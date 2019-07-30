<?php

namespace Drupal\social_lazy_loading_images;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialLazyLoadingTextFormatOverride.
 *
 * @package Drupal\social_lazy_loading_images
 */
class SocialLazyLoadingImageDisplayOverride implements ConfigFactoryOverrideInterface {

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
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    return $overrides;
  }

  /**
   * Alters the filter settings for the text format.
   *
   * @param string $text_format
   *   A config name.
   * @param bool $convert_url
   *   TRUE if filter should be used.
   * @param array $overrides
   *   An override configuration.
   */
  protected function addFilterOverride($text_format, $convert_url, array &$overrides) {
    $config_name = 'filter.format.' . $text_format;

    if ($convert_url) {
      $config = $this->configFactory->getEditable($config_name);
      $dependencies = $config->getOriginal('dependencies.module');
      $overrides[$config_name]['dependencies']['module'] = $dependencies;
      $overrides[$config_name]['dependencies']['module'][] = 'blazy';

      $overrides[$config_name]['filters']['lazy_filter'] = [
        'id' => 'lazy_filter',
        'provider' => 'lazy',
        'status' => TRUE,
        'weight' => 999,
        'settings' => [],
      ];
    }
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

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialLazyLoadingTextFormatOverride';
  }

}

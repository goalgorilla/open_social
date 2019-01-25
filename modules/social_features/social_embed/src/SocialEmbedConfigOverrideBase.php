<?php

namespace Drupal\social_embed;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialEmbedConfigOverrideBase.
 *
 * @package Drupal\social_embed
 */
abstract class SocialEmbedConfigOverrideBase implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory
  ) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $formats = [
      'basic_html' => TRUE,
      'full_html' => FALSE,
    ];

    $this->moduleHandler->alter('social_embed_formats', $formats);

    foreach ($formats as $format => $convert_url) {
      $config_name = $this->getPrefix() . '.' . $format;

      if (in_array($config_name, $names)) {
        /* @var \Drupal\Core\Config\Config $config */
        $config = $this->configFactory->getEditable($config_name);

        $this->doOverride($config, $config_name, $convert_url, $overrides);
      }
    }

    return $overrides;
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
   * Make some changes in text format configuration.
   *
   * @param \Drupal\Core\Config\Config $config
   *   A configuration object.
   * @param string $config_name
   *   A config name.
   * @param bool $convert_url
   *   TRUE if filter should be used.
   * @param array $overrides
   *   An override configuration.
   */
  public function doOverride(Config $config, $config_name, $convert_url, array &$overrides) {
  }

  /**
   * Returns configuration name prefix.
   *
   * @return string
   *   A configuration name prefix.
   */
  public function getPrefix() {
    return '';
  }

}

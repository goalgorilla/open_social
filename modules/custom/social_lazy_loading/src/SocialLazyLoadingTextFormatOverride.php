<?php

namespace Drupal\social_lazy_loading;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialLazyLoadingTextFormatOverride.
 *
 * @package Drupal\social_lazy_loading
 */
class SocialLazyLoadingTextFormatOverride implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $formats = [
      'basic_html' => TRUE,
      'full_html' => TRUE,
      'plain_text' => TRUE,
      'simple_text' => TRUE,
      'restricted_html' => TRUE,
      'mail_html' => TRUE,
    ];

    $this->moduleHandler->alter('social_lazy_loading_formats', $formats);

    foreach ($formats as $format => $convert_url) {
      if (in_array('filter.format.' . $format, $names, FALSE)) {
        $this->addFilterOverride($format, $convert_url, $overrides);
      }
    }

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
      $overrides[$config_name]['dependencies']['module']['lazy'] = 'lazy';
      $overrides[$config_name]['filters']['lazy_filter'] = [
        'id' => 'lazy_filter',
        'provider' => 'lazy',
        'status' => TRUE,
        'weight' => 999,
        'settings' => [
          'image' => TRUE,
          'iframe' => TRUE,
        ],
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

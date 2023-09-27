<?php

namespace Drupal\social_embed;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Configuration overrides for Social Embed module.
 *
 * @package Drupal\social_embed
 */
class SocialEmbedConfigOverride implements ConfigFactoryOverrideInterface {

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
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    $found = FALSE;

    foreach ($names as $name) {
      if (
        strpos($name, 'filter.format.') === 0 ||
        strpos($name, 'editor.editor.') === 0
      ) {
        $found = TRUE;
        break;
      }
    }

    if (!$found) {
      return $overrides;
    }

    $formats = [
      'basic_html' => TRUE,
      'full_html' => FALSE,
    ];

    $this->moduleHandler->alter('social_embed_formats', $formats);

    foreach ($formats as $format => $convert_url) {
      if (in_array('filter.format.' . $format, $names)) {
        $this->addFilterOverride($format, $convert_url, $overrides);
      }

      if (in_array('editor.editor.' . $format, $names)) {
        $this->addEditorOverride($format, $overrides);
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
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->configFactory->getEditable($config_name);
    $filters = $config->get('filters');

    $dependencies = $config->getOriginal('dependencies.module');
    $overrides[$config_name]['dependencies']['module'] = $dependencies;
    $overrides[$config_name]['dependencies']['module'][] = 'url_embed';

    $overrides[$config_name]['filters']['social_embed_url_embed'] = [
      'id' => 'social_embed_url_embed',
      'provider' => 'social_embed',
      'status' => TRUE,
      'weight' => 100,
      'settings' => [],
    ];

    if ($convert_url) {
      $overrides[$config_name]['filters']['social_embed_convert_url'] = [
        'id' => 'social_embed_convert_url',
        'provider' => 'social_embed',
        'status' => TRUE,
        'weight' => (isset($filters['filter_url']['weight']) ? $filters['filter_url']['weight'] - 1 : 99),
        'settings' => [
          'url_prefix' => '',
        ],
      ];

      if (isset($filters['filter_html'])) {
        $overrides[$config_name]['filters']['filter_html']['settings']['allowed_html'] = $filters['filter_html']['settings']['allowed_html'] . ' <drupal-url data-*>';
      }
    }
  }

  /**
   * Alters the editor settings for the text format.
   *
   * @param string $text_format
   *   The text format to adjust.
   * @param array $overrides
   *   An override configuration.
   */
  protected function addEditorOverride($text_format, array &$overrides) {
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
    return 'SocialEmbedConfigOverride';
  }

}

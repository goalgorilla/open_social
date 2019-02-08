<?php

namespace Drupal\social_embed;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialEmbedConfigOverride.
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
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->configFactory->getEditable($config_name);

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
   * Alters the editor settings for the text format.
   *
   * @param string $text_format
   *   The text format to adjust.
   * @param array $overrides
   *   An override configuration.
   */
  protected function addEditorOverride($text_format, array &$overrides) {
    $config_name = 'editor.editor.' . $text_format;
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->configFactory->getEditable($config_name);
    $settings = $config->get('settings');

    if (!($settings && isset($settings['toolbar']['rows']) && is_array($settings['toolbar']['rows']))) {
      return;
    }

    $button_exists = FALSE;

    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        foreach ($group['items'] as $button) {
          if ($button === 'social_embed') {
            $button_exists = TRUE;
            break;
          }
        }
      }
    }

    if (!$button_exists) {
      $row_array_keys = array_keys($settings['toolbar']['rows']);
      $last_row_key = end($row_array_keys);

      $group = [];
      $group['name'] = 'Embed';
      $group['items'] = [];
      $group['items'][] = 'social_embed';
      $settings['toolbar']['rows'][$last_row_key][] = $group;

      $overrides[$config_name] = [
        'settings' => $settings,
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
    return 'SocialEmbedConfigOverride';
  }

}

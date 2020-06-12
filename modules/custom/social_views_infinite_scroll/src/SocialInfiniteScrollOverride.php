<?php

namespace Drupal\social_views_infinite_scroll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\views\Views;

/**
 * Class SocialInfiniteScrollOverride.
 *
 * @package Drupal\social_views_infinite_scroll
 */
class SocialInfiniteScrollOverride implements ConfigFactoryOverrideInterface {

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
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $scroll_config = $this->configFactory->getEditable('social_views_infinite_scroll.settings');
    $scroll_data = $scroll_config->getOriginal();

    foreach ($scroll_data as $key => $scroll_datum) {
      if ($scroll_datum) {

        $config_name = str_replace('__', '.', $key);
        if (in_array($config_name, $names)) {
          $current_view = $this->configFactory->getEditable($config_name);

          $display = NULL;
          foreach($current_view->get('display') as $index => $data) {
            if (strpos($index, 'page') !== FALSE)
              $display = $index;
          }
          if (!$display) {
            return [];
          }

          $overrides[$config_name] = [
            'display' => [
              $display => [
                'display_options' => [
                  'use_ajax' => TRUE,
                  'pager' => [
                    'type' => 'infinite_scroll',
                    'options' => [
                      'views_infinite_scroll' => [
                        'button_text' => 'Load More',
                        'automatically_load_content' => TRUE,
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ];

        }
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialInfiniteScrollOverride';
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

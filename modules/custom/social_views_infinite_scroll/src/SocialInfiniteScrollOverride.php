<?php

namespace Drupal\social_views_infinite_scroll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

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
   * The SocialInfiniteScrollManager manager.
   *
   * @var \Drupal\social_views_infinite_scroll\SocialInfiniteScrollManager
   */
  protected $socialInfiniteScrollManager;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\social_views_infinite_scroll\SocialInfiniteScrollManager $social_infinite_scroll_manager
   *   The SocialInfiniteScrollManager manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SocialInfiniteScrollManager $social_infinite_scroll_manager) {
    $this->configFactory = $config_factory;
    $this->socialInfiniteScrollManager = $social_infinite_scroll_manager;
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


          foreach ($scroll_datum as $view_display) {
            if ($view_display) {
              $display_options = $current_view->getOriginal('display.' . $view_display . '.display_options');
              $overrides[$config_name]['display'][$view_display]['display_options'] = array_merge($display_options, [
                'use_ajax' => TRUE,
                'pager' => [
                  'type' => 'infinite_scroll',
                  'options' => [
                    'views_infinite_scroll' => [
                      'button_text' => 'Load More',
                      'automatically_load_content' => FALSE,
                    ],
                  ],
                ],
              ]);


            }
          }

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

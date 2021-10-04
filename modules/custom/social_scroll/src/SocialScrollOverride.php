<?php

namespace Drupal\social_scroll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Override for making pager options infinity scroll.
 *
 * @package Drupal\social_scroll
 */
class SocialScrollOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The social scroll manager.
   *
   * @var \Drupal\social_scroll\SocialScrollManagerInterface
   */
  protected $socialScrollManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\social_scroll\SocialScrollManagerInterface $social_scroll_manager
   *   The SocialScrollManager manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SocialScrollManagerInterface $social_scroll_manager, RouteMatchInterface $route_match) {
    $this->configFactory = $config_factory;
    $this->socialScrollManager = $social_scroll_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   *
   * @param string[] $names
   *   A list of configuration names that are being loaded.
   *
   * @return mixed[]
   *   An array keyed by configuration name of override data. Override data
   *   contains a nested array structure of overrides.
   *
   * @codingStandardsIgnoreStart
   */
  public function loadOverrides($names): array {
    // @codingStandardsIgnoreEnd
    $overrides = [];
    $enabled_views = $this->socialScrollManager->getEnabledViewIds();

    foreach ($enabled_views as $key) {
      $config_name = $this->socialScrollManager->getConfigName($key);

      if (in_array($config_name, $names)) {
        $current_view = $this->configFactory->getEditable($config_name);
        $displays = $current_view->getOriginal('display');

        $scroll_config = $this->configFactory->getEditable('social_scroll.settings');
        $button_text = $scroll_config->getOriginal('button_text');
        $automatically_load_content = $scroll_config->getOriginal('automatically_load_content');

        $pages = [];

        foreach ($displays as $id => $display) {
          if (isset($display['display_options']['pager']) && $display['display_plugin'] !== 'block') {
            $pages[] = $id;
          }
        }

        foreach ($pages as $display_page) {
          $display_options = $current_view->getOriginal('display.' . $display_page . '.display_options');
          $overrides[$config_name]['display'][$display_page]['display_options'] = array_merge($display_options, [
            'pager' => [
              'type' => 'infinite_scroll',
              'options' => [
                'views_infinite_scroll' => [
                  'button_text' => $button_text,
                  'automatically_load_content' => $automatically_load_content,
                ],
              ],
            ],
            'use_ajax' => TRUE,
          ]);
        }

      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix(): string {
    return 'SocialScrollOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * Creates a configuration object for use during install and synchronization.
   *
   * @param string $name
   *   The configuration object name.
   * @param string $collection
   *   The configuration collection.
   *
   * @return \Drupal\Core\Config\StorableConfigBase|null
   *   The configuration object for the provided name and collection.
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}

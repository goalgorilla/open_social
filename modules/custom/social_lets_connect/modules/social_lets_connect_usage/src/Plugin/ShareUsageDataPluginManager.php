<?php

namespace Drupal\social_lets_connect_usage\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Share usage data plugin plugin manager.
 */
class ShareUsageDataPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new ShareUsageDataPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ShareUsageDataPlugin', $namespaces, $module_handler, 'Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPluginInterface', 'Drupal\social_lets_connect_usage\Annotation\ShareUsageDataPlugin');

    $this->alterInfo('social_lets_connect_usage_share_usage_data_plugin_info');
    $this->setCacheBackend($cache_backend, 'social_lets_connect_usage_share_usage_data_plugin_plugins');
  }

}

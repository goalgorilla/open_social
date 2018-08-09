<?php

namespace Drupal\social_user_export\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the User export plugin plugin manager.
 */
class UserExportPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new UserExportPluginManager object.
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
    parent::__construct('Plugin/UserExportPlugin', $namespaces, $module_handler, 'Drupal\social_user_export\Plugin\UserExportPluginInterface', 'Drupal\social_user_export\Annotation\UserExportPlugin');

    $this->alterInfo('social_user_export_plugin_info');
    $this->setCacheBackend($cache_backend, 'social_user_export_user_export_plugin_plugins');
  }

  /**
   * Finds plugin definitions.
   *
   * @return array
   *   List of definitions to store in cache.
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    usort($definitions, [$this, 'sortDefinitions']);
    return $definitions;
  }

  /**
   * Order by weight.
   *
   * @param array $a
   *   First parameter.
   * @param array $b
   *   Second parameter.
   *
   * @return int
   *   The weight to be used for the usort function.
   */
  protected function sortDefinitions(array $a, array $b) {
    if (isset($a['weight']) && isset($b['weight'])) {
      return $a['weight'] < $b['weight'] ? -1 : 1;
    }
    return 0;
  }

}

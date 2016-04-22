<?php

namespace Drupal\search_api\Datasource;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages datasource plugins.
 *
 * @see \Drupal\search_api\Annotation\SearchApiDatasource
 * @see \Drupal\search_api\Datasource\DatasourceInterface
 * @see \Drupal\search_api\Datasource\DatasourcePluginBase
 * @see plugin_api
 */
class DatasourcePluginManager extends DefaultPluginManager {

  /**
   * Constructs a DatasourcePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api/datasource', $namespaces, $module_handler, 'Drupal\search_api\Datasource\DatasourceInterface', 'Drupal\search_api\Annotation\SearchApiDatasource');
    $this->setCacheBackend($cache_backend, 'search_api_datasources');
    $this->alterInfo('search_api_datasource_info');
  }

}

<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\kpi_analytics\Annotation\KPIDatasource;

/**
 * Provides the KPI Datasource plugin manager.
 */
class KPIDatasourceManager extends DefaultPluginManager {

  /**
   * Constructor for KPIDatasourceManager objects.
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
    parent::__construct('Plugin/KPIDatasource', $namespaces, $module_handler, KPIDatasourceInterface::class, KPIDatasource::class);

    $this->alterInfo('kpi_analytics_kpi_datasource_info');
    $this->setCacheBackend($cache_backend, 'kpi_analytics_kpi_datasource_plugins');
  }

  /**
   * Retrieves an options list of available trackers.
   *
   * @return string[]
   *   An associative array mapping the IDs of all available tracker plugins to
   *   their labels.
   */
  public function getOptionsList(): array {
    $options = [];
    foreach ($this->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = Html::escape($plugin_definition['label']);
    }
    return $options;
  }

}

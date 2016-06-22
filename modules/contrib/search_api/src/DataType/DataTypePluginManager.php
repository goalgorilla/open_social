<?php

namespace Drupal\search_api\DataType;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages data type plugins.
 *
 * @see \Drupal\search_api\Annotation\SearchApiDataType
 * @see \Drupal\search_api\DataType\DataTypeInterface
 * @see \Drupal\search_api\DataType\DataTypePluginBase
 * @see plugin_api
 */
class DataTypePluginManager extends DefaultPluginManager {

  /**
   * Static cache for the data type definitions.
   *
   * @var string[][]
   *
   * @see \Drupal\search_api\DataType\DataTypePluginManager::getInstances()
   */
  protected $dataTypes;

  /**
   * Constructs a DataTypePluginManager object.
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
    parent::__construct('Plugin/search_api/data_type', $namespaces, $module_handler, 'Drupal\search_api\DataType\DataTypeInterface', 'Drupal\search_api\Annotation\SearchApiDataType');

    $this->setCacheBackend($cache_backend, 'search_api_data_type');
    $this->alterInfo('search_api_data_type_info');
  }

  /**
   * Returns all known data types.
   *
   * @return \Drupal\search_api\DataType\DataTypeInterface[]
   *   An array of data type plugins, keyed by type identifier.
   */
  public function getInstances() {
    if (!isset($this->DataTypes)) {
      $this->dataTypes = array();

      foreach ($this->getDefinitions() as $name => $data_type_definition) {
        if (class_exists($data_type_definition['class']) && empty($this->dataTypes[$name])) {
          $data_type = $this->createInstance($name);
          $this->dataTypes[$name] = $data_type;
        }
      }
    }

    return $this->dataTypes;
  }

  /**
   * Returns all field data types known by the Search API as an options list.
   *
   * @return string[]
   *   An associative array with all recognized types as keys, mapped to their
   *   translated display names.
   *
   * @see \Drupal\search_api\DataTypePluginManager::getInstances()
   */
  public function getInstancesOptions() {
    $types = array();
    foreach ($this->getInstances() as $id => $info) {
      $types[$id] = $info->label();
    }

    return $types;
  }

}

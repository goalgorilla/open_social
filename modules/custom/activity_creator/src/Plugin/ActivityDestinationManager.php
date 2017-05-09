<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityDestinationManager.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Activity destination plugin manager.
 */
class ActivityDestinationManager extends DefaultPluginManager {

  /**
   * Constructor for ActivityDestinationManager objects.
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
    parent::__construct('Plugin/ActivityDestination', $namespaces, $module_handler, 'Drupal\activity_creator\Plugin\ActivityDestinationInterface', 'Drupal\activity_creator\Annotation\ActivityDestination');

    $this->alterInfo('activity_creator_activity_destination_info');
    $this->setCacheBackend($cache_backend, 'activity_creator_activity_destination_plugins');
  }

  /**
   * Retrieves an options list of available trackers.
   *
   * @return string[]
   *   An associative array mapping the IDs of all available tracker plugins to
   *   their labels.
   */
  public function getOptionsList() {
    $options = array();
    foreach ($this->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = Html::escape($plugin_definition['label']);
    }
    return $options;
  }

  /**
   * Retrieves an list of available destinations by given properties.
   *
   * @param $condition
   * @param $value
   *
   * @return string[]
   *   An array of the IDs of all available destination plugins.
   */
  public function getListByProperties($condition = NULL, $value = NULL) {
    $options = array();
    foreach ($this->getDefinitions() as $plugin_id => $plugin_definition) {
      if (empty($condition) || (isset($plugin_definition[$condition]) && $plugin_definition[$condition] === $value)) {
        $options[] = $plugin_id;
      }
    }
    return $options;

  }

}

<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContextManager.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides the Activity context plugin manager.
 */
class ActivityContextManager extends DefaultPluginManager {

  /**
   * Constructor for ActivityContextManager objects.
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
    parent::__construct('Plugin/ActivityContext', $namespaces, $module_handler, 'Drupal\activity_creator\Plugin\ActivityContextInterface', 'Drupal\activity_creator\Annotation\ActivityContext');

    $this->alterInfo('activity_creator_activity_context_info');
    $this->setCacheBackend($cache_backend, 'activity_creator_activity_context_plugins');
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

}

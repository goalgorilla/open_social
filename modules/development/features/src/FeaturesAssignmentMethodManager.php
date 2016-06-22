<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesAssignmentMethodManager.
 */

namespace Drupal\features;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages configuration packaging methods.
 */
class FeaturesAssignmentMethodManager extends DefaultPluginManager {

  /**
   * Constructs a new FeaturesAssignmentMethodManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   An object that implements CacheBackendInterface.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   An object that implements ModuleHandlerInterface.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FeaturesAssignment', $namespaces, $module_handler,
      'Drupal\features\FeaturesAssignmentMethodInterface');
    $this->alterInfo('features_assignment_info');
    $this->setCacheBackend($cache_backend, 'features_assignment_methods');
  }

}

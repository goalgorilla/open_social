<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesGenerationMethodManager.
 */

namespace Drupal\features;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages configuration packaging methods.
 */
class FeaturesGenerationMethodManager extends DefaultPluginManager {

  /**
   * Constructs a new FeaturesGenerationMethodManager object.
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
    parent::__construct('Plugin/FeaturesGeneration', $namespaces, $module_handler, 'Drupal\features\FeaturesGenerationMethodInterface');
    $this->cacheBackend = $cache_backend;
    $this->cacheKeyPrefix = 'features_generation_methods';
    $this->cacheKey = 'features_generation_methods';
    $this->alterInfo('features_generation_info');
  }

}

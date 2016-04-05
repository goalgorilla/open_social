<?php

namespace Drupal\facets\FacetSource;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages facet source plugins.
 *
 * @see \Drupal\facets\Annotation\FacetsFacetSource
 * @see \Drupal\facets\FacetSource\FacetSourcePluginBase
 * @see plugin_api
 */
class FacetSourcePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets/facet_source', $namespaces, $module_handler, 'Drupal\facets\FacetSource\FacetSourcePluginInterface', 'Drupal\facets\Annotation\FacetsFacetSource');
  }

}

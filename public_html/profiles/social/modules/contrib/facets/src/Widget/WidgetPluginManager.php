<?php

namespace Drupal\facets\Widget;


use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines a plugin manager for widgets.
 */
class WidgetPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets/widget', $namespaces, $module_handler, 'Drupal\facets\Widget\WidgetInterface', 'Drupal\facets\Annotation\FacetsWidget');
  }

}

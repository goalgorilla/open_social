<?php

namespace Drupal\facets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Facets facet source annotation.
 *
 * @see \Drupal\facets\FacetSource\FacetSourcePluginManager
 * @see \Drupal\facets\FacetSource\FacetSourcePluginInterface
 * @see \Drupal\facets\FacetSource\FacetSourcePluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class FacetsFacetSource extends Plugin {

  /**
   * The facet source plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the facet source plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The facet source description.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}

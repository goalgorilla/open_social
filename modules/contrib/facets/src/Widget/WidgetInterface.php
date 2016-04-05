<?php

namespace Drupal\facets\Widget;

use Drupal\facets\FacetInterface;

/**
 * Interface describing the widgets.
 */
interface WidgetInterface {

  /**
   * Builds the widget for rendering.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet we need to build.
   *
   * @return array
   *   A renderable array.
   */
  public function build(FacetInterface $facet);

  /**
   * Picks the preferred query type for this widget.
   *
   * @param string[] $query_types
   *   An array keyed with query type name and it's plugin class to load.
   *
   * @return string
   *   The query type plugin class to load.
   */
  public function getQueryType($query_types);

}

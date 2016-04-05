<?php

namespace Drupal\core_search_facets\Plugin;

use Drupal\facets\FacetInterface;

/**
 * Additional interface for core facet sources.
 *
 * A facet source is used to abstract the data source where facets can be added
 * to. A good example of this is a Search API view. There are other possible
 * facet data sources, these all implement the FacetSourcePluginInterface.
 */
interface CoreSearchFacetSourceInterface {

  /**
   * Sets the facet query object.
   *
   * @return \Drupal\core_search_facets\FacetsQuery
   *   The facet query object.
   */
  public function getFacetQueryExtender();

  /**
   * Returns the query info for this facet field.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet definition as returned by facets_facet_load().
   *
   * @return array
   *   An associative array containing:
   *   - fields: An array of field information, each of which are associative
   *      arrays containing:
   *      - table_alias: The table alias the field belongs to.
   *      - field: The name of the field containing the facet data.
   *    - joins: An array of join info, each of which are associative arrays
   *      containing:
   *      - table: The table being joined.
   *      - alias: The alias of the table being joined.
   *      - condition: The condition that joins the table.
   */
  public function getQueryInfo(FacetInterface $facet);

}

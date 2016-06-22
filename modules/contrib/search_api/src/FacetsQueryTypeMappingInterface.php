<?php

namespace Drupal\search_api;

/**
 * Defines a method for mapping Search API data types to Facets query types.
 */
interface FacetsQueryTypeMappingInterface {

  /**
   * Alters the query types for a specified data type.
   *
   * Backend plugins can use this method to override the default query types
   * provided by the Search API with backend-specific ones that better use
   * features of that backend.
   *
   * @param array $mapping
   *   An associative array mapping data type IDs to arrays of Facets query type
   *   plugin IDs compatible with that data type.
   */
  public function alterFacetQueryTypeMapping(array &$mapping);

}

<?php

namespace Drupal\facets\QueryType;

/**
 * The interface defining the required methods for a query type.
 */
interface QueryTypeInterface {

  /**
   * Adds facet info to the query using the backend native query object.
   *
   * @return array
   *   Returns an array of unfiltered results
   */
  public function execute();

  /**
   * Builds the facet information, so it can be rendered.
   */
  public function build();

}

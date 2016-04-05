<?php

namespace Drupal\facets\FacetSource;

/**
 * A facet source that uses Search API as a base.
 */
interface SearchApiFacetSourceInterface {

  /**
   * Returns the search_api index.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The Search API index.
   */
  public function getIndex();

}

<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;

/**
 * Defines a filter for filtering on taxonomy term references.
 *
 * Based on \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_term")
 */
class SearchApiTerm extends TaxonomyIndexTid {

  use UncacheableDependencyTrait;
  use SearchApiFilterTrait;

}

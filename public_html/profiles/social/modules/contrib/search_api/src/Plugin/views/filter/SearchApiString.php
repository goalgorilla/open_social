<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\search_api\UncacheableDependencyTrait;

/**
 * Defines a filter for adding conditions on string fields to the query.
 *
 * Due to the way the Search API works, this inherits from the numeric handler,
 * since the operators most closely resemble those from Views' own numeric
 * filter. (The Search API doesn't have operators for "contains", "starts
 * with", etc. as used by Views' string filter.)
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_string")
 */
class SearchApiString extends SearchApiNumeric {

  use UncacheableDependencyTrait;

}

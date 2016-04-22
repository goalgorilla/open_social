<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\views\Plugin\views\filter\Date;

/**
 * Defines a filter for filtering on dates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_date")
 */
class SearchApiDate extends Date {

  use UncacheableDependencyTrait;
  use SearchApiFilterTrait;

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();
    // @todo Enable "(not) between" again once that operator is available in
    //   the Search API.
    unset($operators['between'], $operators['not between'], $operators['regular_expression']);
    return $operators;
  }

}

<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\views\Plugin\views\filter\NumericFilter;

/**
 * Defines a filter for filtering on numeric values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_numeric")
 */
class SearchApiNumeric extends NumericFilter {

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

  /**
   * {@inheritdoc}
   */
  protected function opEmpty($field) {
    $this->getQuery()->addCondition($this->realField, NULL, $this->operator == 'empty' ? '=' : '<>', $this->options['group']);
  }

}

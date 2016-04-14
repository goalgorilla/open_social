<?php

namespace Drupal\search_api\Plugin\views\filter;
use Drupal\search_api\UncacheableDependencyTrait;

/**
 * Defines a filter for filtering on fulltext fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_text")
 */
class SearchApiText extends SearchApiString {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();

    $operators['=']['title'] = $this->t('contains');
    $operators['!=']['title'] = $this->t("doesn't contain");

    $operators = array_intersect_key($operators, array('=' => 1, '!=' => 1));

    return $operators;
  }

}

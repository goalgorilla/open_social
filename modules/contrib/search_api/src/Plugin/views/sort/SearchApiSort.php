<?php

namespace Drupal\search_api\Plugin\views\sort;

use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Provides a sort plugin for Search API views.
 *
 * @ViewsSort("search_api")
 */
class SearchApiSort extends SortPluginBase {

  use UncacheableDependencyTrait;

  /**
   * The associated views query object.
   *
   * @var \Drupal\search_api\Plugin\views\query\SearchApiQuery
   */
  public $query;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // When there are exposed sorts, the "exposed form" plugin will set
    // $query->orderby to an empty array. Therefore, if that property is set,
    // we here remove all previous sorts.
    // @todo Is this still true in D8?
    // @todo Check whether #2145547 is still a problem here.
    if (isset($this->query->orderby)) {
      unset($this->query->orderby);
      $sort = &$this->query->getSort();
      $sort = array();
    }
    $this->query->sort($this->realField, $this->options['order']);
  }

}

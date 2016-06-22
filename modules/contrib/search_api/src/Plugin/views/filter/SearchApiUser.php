<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\user\Plugin\views\filter\Name;

/**
 * Defines a filter for filtering on user references.
 *
 * Based on \Drupal\user\Plugin\views\filter\Name.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_user")
 */
class SearchApiUser extends Name {

  use UncacheableDependencyTrait;
  use SearchApiFilterTrait;

  /**
   * {@inheritdoc}
   */
  public function operators() {
    return array(
      'or' => array(
        'title' => $this->t('Is one of'),
        'short' => $this->t('or'),
        'short_single' => $this->t('='),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ),
      'and' => array(
        'title' => $this->t('Is all of'),
        'short' => $this->t('and'),
        'short_single' => $this->t('='),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ),
      'not' => array(
        'title' => $this->t('Is none of'),
        'short' => $this->t('not'),
        'short_single' => $this->t('<>'),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ),
      'empty' => array(
        'title' => $this->t('Is empty (NULL)'),
        'method' => 'opEmpty',
        'short' => $this->t('empty'),
        'values' => 0,
      ),
      'not empty' => array(
        'title' => $this->t('Is not empty (NOT NULL)'),
        'method' => 'opEmpty',
        'short' => $this->t('not empty'),
        'values' => 0,
      ),
    );
  }

}

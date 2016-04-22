<?php

namespace Drupal\search_api\Plugin\views\join;

use Drupal\views\Plugin\views\join\JoinPluginBase;

/**
 * Represents a join in the Search API Views tables.
 *
 * Since the concept of joins doesn't exist in the Search API, this handler does
 * nothing except override the default behavior and thus enable the joining of
 * Views data tables in Search API views.
 *
 * @ingroup views_join_handlers
 *
 * @ViewsJoin("search_api")
 */
class SearchApiJoin extends JoinPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {}

}

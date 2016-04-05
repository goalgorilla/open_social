<?php

namespace Drupal\core_search_facets;

use Drupal\search\SearchQuery;

/**
 * Extension of the SearchQuery class.
 */
class FacetsQuery extends SearchQuery {

  /**
   * Stores joined tables.
   */
  protected $joinedTables = array();

  /**
   * Adds the facet join, but only does so once.
   *
   * @param array $query_info
   *   An associative array of query information.
   * @param string $table_alias
   *   The alias of the table being joined.
   */
  public function addFacetJoin(array $query_info, $table_alias) {
    if (isset($query_info['joins'][$table_alias])) {
      if (!isset($this->joinedTables[$table_alias])) {
        $this->joinedTables[$table_alias] = TRUE;
        $join_info = $query_info['joins'][$table_alias];
        $this->join($join_info['table'], $join_info['alias'], $join_info['condition']);
      }
    }
  }

  /**
   * Adds the facet field, ensures the alias is "value".
   *
   * @param array $query_info
   *   An associative array of query information.
   *
   * @return FacetsQuery
   *    An instance of this class.
   */
  public function addFacetField(array $query_info) {
    foreach ($query_info['fields'] as $field_info) {
      $this->addField($field_info['table_alias'], $field_info['field'], 'value');
    }
    return $this;
  }

  /**
   * Executes a facet query.
   */
  public function execute() {
    $this->parseSearchExpression();

    // Adds OR conditions.
    if (!empty($this->words)) {
      $or = db_or();
      foreach ($this->words as $word) {
        $or->condition('i.word', $word);
      }
      $this->condition($or);
    }

    // Build query for keyword normalization.
    $this->join('search_total', 't', 'i.word = t.word');
    $this
      ->condition('i.type', $this->type)
      ->groupBy('i.langcode')
      ->groupBy('value')
      ->groupBy('i.type')
      ->groupBy('i.sid')
      ->having('COUNT(*) >= :matches', array(':matches' => $this->matches));

    // For complex search queries, add the LIKE conditions.
    /*if (!$this->simple) {
    $this->join('search_dataset', 'd', 'i.sid = d.sid AND i.type = d.type');
    $this->condition($this->conditions);
    }*/

    // Add conditions to query.
    $this->join('search_dataset', 'd', 'i.sid = d.sid AND i.type = d.type AND i.langcode = d.langcode');
    $this->condition($this->conditions);

    // Add tag and useful metadata.
    $this
      ->addTag('search_' . $this->type)
      ->addMetaData('normalize', $this->normalize);

    // Adds subquery to group the results in the r table.
    $subquery = db_select($this->query, 'r')
      ->fields('r', array('value'))
      ->groupBy('r.value')
      ->orderBy('count', 'DESC');

    // Adds COUNT() expression to get facet counts.
    $subquery->addExpression('COUNT(r.value)', 'count');

    // Executes the subquery.
    return $subquery->execute();
  }

  /**
   * Returns the search expression.
   *
   * @return string
   *   The search expression.
   */
  public function getSearchExpression() {
    return $this->searchExpression;
  }

}

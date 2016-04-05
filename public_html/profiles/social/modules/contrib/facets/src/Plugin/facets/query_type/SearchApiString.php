<?php

namespace Drupal\facets\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;

/**
 * Provides support for string facets within the Search API scope.
 *
 * This is the default implementation that works with all backends and data
 * types. While you could use this query type for every data type, other query
 * types will usually be better suited for their specific data type.
 *
 * For example, the SearchApiDate query type will handle its input as a DateTime
 * value, while this class would only be able to work with it as a string.
 *
 * @FacetsQueryType(
 *   id = "search_api_string",
 *   label = @Translation("String"),
 * )
 */
class SearchApiString extends QueryTypePluginBase {

  /**
   * The backend's native query object.
   *
   * @var \Drupal\search_api\Query\QueryInterface
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query = $this->query;

    $unfiltered_results = [];

    // Only alter the query when there's an actual query object to alter.
    if (!empty($query)) {
      $operator = $this->facet->getQueryOperator();
      $field_identifier = $this->facet->getFieldIdentifier();
      $exclude = $this->facet->getExclude();

      // Copy the query object so we can do an unfiltered query. We need to have
      // this unfiltered results to make sure that the count of a facet is
      // correct. The unfiltered results get returned to the facet manager, the
      // facet manager will save it on facet::unfiltered_results.
      $unfiltered_query = $query;
      $unfiltered_options = &$unfiltered_query->getOptions();
      $unfiltered_options['search_api_facets'][$field_identifier] = array(
        'field' => $field_identifier,
        'limit' => 50,
        'operator' => 'and',
        'min_count' => 0,
        'missing' => FALSE,
      );
      $unfiltered_results = $unfiltered_query
        ->execute()
        ->getExtraData('search_api_facets');

      // Set the options for the actual query.
      $options = &$query->getOptions();
      $options['search_api_facets'][$field_identifier] = array(
        'field' => $field_identifier,
        'limit' => 50,
        'operator' => 'and',
        'min_count' => 0,
        'missing' => FALSE,
      );

      // Add the filter to the query if there are active values.
      $active_items = $this->facet->getActiveItems();

      if (count($active_items)) {
        $filter = $query->createConditionGroup($operator);
        foreach ($active_items as $value) {
          $filter->addCondition($this->facet->getFieldIdentifier(), $value, $exclude ? '<>' : '=');
        }
        $query->addConditionGroup($filter);
      }
    }

    return $unfiltered_results;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query_operator = $this->facet->getQueryOperator();

    if (!empty($this->results)) {
      $facet_results = array();
      foreach ($this->results as $key => $result) {
        if ($result['count'] || $query_operator == 'OR') {
          $count = $result['count'];
          if ($query_operator === 'OR') {
            $unfiltered_results = $this->facet->getUnfilteredResults();
            $field_identifier = $this->facet->getFieldIdentifier();

            foreach ($unfiltered_results[$field_identifier] as $unfiltered_result) {
              if ($unfiltered_result['filter'] === $result['filter']) {
                $count = $unfiltered_result['count'];
              }
            }
          }

          $result = new Result(trim($result['filter'], '"'), trim($result['filter'], '"'), $count);
          $facet_results[] = $result;
        }
      }
      $this->facet->setResults($facet_results);
    }
    return $this->facet;
  }

}

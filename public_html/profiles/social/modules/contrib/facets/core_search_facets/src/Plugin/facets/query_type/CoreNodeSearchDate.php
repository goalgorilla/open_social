<?php

namespace Drupal\core_search_facets\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;
use Drupal\facets\Result\ResultInterface;

/**
 * A date query type for core search.
 *
 * @FacetsQueryType(
 *   id = "core_node_search_date",
 *   label = @Translation("Date"),
 * )
 */
class CoreNodeSearchDate extends QueryTypePluginBase {

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
    /** @var \Drupal\facets\Utility\FacetsDateHandler $date_handler */
    $date_handler = \Drupal::getContainer()->get('facets.utility.date_handler');

    /** @var \Drupal\core_search_facets\Plugin\CoreSearchFacetSourceInterface $facet_source */
    $facet_source = $this->facet->getFacetSource();

    // Gets the last active date, bails if there isn't one.
    $active_items = $this->facet->getActiveItems();
    if (!$active_item = end($active_items)) {
      return;
    }

    // Gets facet query and this facet's query info.
    /** @var \Drupal\core_search_facets\FacetsQuery $facet_query */
    $facet_query = $facet_source->getFacetQueryExtender();
    $query_info = $facet_source->getQueryInfo($this->facet);
    $tables_joined = [];

    $active_item = $date_handler->extractActiveItems($active_item);

    foreach ($query_info['fields'] as $field_info) {

      // Adds join to the facet query.
      $facet_query->addFacetJoin($query_info, $field_info['table_alias']);

      // Adds join to search query, makes sure it is only added once.
      if (isset($query_info['joins'][$field_info['table_alias']])) {
        if (!isset($tables_joined[$field_info['table_alias']])) {
          $tables_joined[$field_info['table_alias']] = TRUE;
          $join_info = $query_info['joins'][$field_info['table_alias']];
          $this->query->join($join_info['table'], $join_info['alias'], $join_info['condition']);
        }
      }

      // Adds field conditions to the facet and search query.
      $field = $field_info['table_alias'] . '.' . $field_info['field'];
      $this->query->condition($field, $active_item['start']['timestamp'], '>=');
      $this->query->condition($field, $active_item['end']['timestamp'], '<');
      $facet_query->condition($field, $active_item['start']['timestamp'], '>=');
      $facet_query->condition($field, $active_item['end']['timestamp'], '<');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $parent_facet_results = [];
    /** @var \Drupal\facets\Utility\FacetsDateHandler $date_handler */
    $date_handler = \Drupal::getContainer()->get('facets.utility.date_handler');

    // Gets base facet query, adds facet field and filters.
    /* @var \Drupal\core_search_facets\Plugin\CoreSearchFacetSourceInterface $facet_source */
    $facet_source = $this->facet->getFacetSource();
    $query_info = $facet_source->getQueryInfo($this->facet);

    /** @var \Drupal\core_search_facets\FacetsQuery $facet_query */
    $facet_query = $facet_source->getFacetQueryExtender();
    $facet_query->addFacetField($query_info);

    foreach ($query_info['joins'] as $table_alias => $join_info) {
      $facet_query->addFacetJoin($query_info, $table_alias);
    }

    if ($facet_query->getSearchExpression()) {
      // Executes query, iterates over results.
      $result = $facet_query->execute();

      foreach ($result as $record) {
        $raw_values[$record->value] = $record->count;
      }
      ksort($raw_values);

      // Gets active facets, starts building hierarchy.
      $parent = NULL;
      $gap = NULL;
      $last_parent = NULL;

      foreach ($this->facet->getActiveItems() as $value => $item) {
        if ($active_item = $date_handler->extractActiveItems($item)) {
          $date_gap = $date_handler->getDateGap($active_item['start']['iso'], $active_item['end']['iso']);
          $gap = $date_handler->getNextDateGap($date_gap, $date_handler::FACETS_DATE_MINUTE);
          $last_parent = '[' . $active_item['start']['iso'] . ' TO ' . $active_item['end']['iso'] . ']';
          $result = new Result($last_parent, $date_handler->formatTimestamp($active_item['start']['timestamp'], $date_gap), NULL);
          $result->setActiveState(TRUE);
          // Sets the children for the current parent..
          if ($parent) {
            $parent->setChildren($result);
          }
          else {
            $parent = $parent_facet_results[] = $result;
          }
        }
      }

      // Mind the gap! Calculates gap from min and max timestamps.
      $timestamps = array_keys($raw_values);
      if (is_null($parent)) {
        if (count($raw_values) > 1) {
          $gap = $date_handler->getTimestampGap(min($timestamps), max($timestamps));
        }
        else {
          $gap = $date_handler::FACETS_DATE_HOUR;
        }
      }

      // Converts all timestamps to dates in ISO 8601 format.
      $dates = [];
      foreach ($timestamps as $timestamp) {
        $dates[$timestamp] = $date_handler->isoDate($timestamp, $gap);
      }

      // Treat each date as the range start and next date as the range end.
      $range_end = [];
      $previous = NULL;
      foreach (array_unique($dates) as $date) {
        if (!is_null($previous)) {
          $range_end[$previous] = $date_handler->getNextDateIncrement($previous, $gap);
        }
        $previous = $date;
      }
      $range_end[$previous] = $date_handler->getNextDateIncrement($previous, $gap);

      $facet_results = [];
      foreach ($raw_values as $value => $count) {
        $new_value = '[' . $dates[$value] . ' TO ' . $range_end[$dates[$value]] . ']';

        // Avoid to repeat the last value.
        if ($new_value === $last_parent) {
          $this->facet->setResults($parent_facet_results);
          return $this->facet;
        }

        // Groups dates by the range they belong to.
        /** @var \Drupal\facets\Result\Result $last_element */
        $last_value = end($facet_results);
        if ($last_value) {
          if ($new_value != $last_value->getRawValue()) {
            $facet_results[] = new Result($new_value, $date_handler->formatTimestamp($value, $gap), $count);
          }
          else {
            $last_value->setCount($last_value->getCount() + 1);
          }
        }
        else {
          $facet_results[] = new Result($new_value, $date_handler->formatTimestamp($value, $gap), $count);
        }
      }

      // Populate the parent with children.
      $parent = end($parent_facet_results);
      if ($parent instanceof ResultInterface) {
        foreach ($facet_results as $result) {
          $parent->setChildren($result);
          $this->facet->setResults($parent_facet_results);
        }
      }
      else {
        // Set results directly when missing parents.
        $this->facet->setResults($facet_results);
      }
    }

    return $this->facet;
  }

}

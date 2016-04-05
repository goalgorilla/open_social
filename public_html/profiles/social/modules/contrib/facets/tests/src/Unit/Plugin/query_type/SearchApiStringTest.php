<?php

namespace Drupal\Tests\facets\Unit\Plugin\query_type;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\query_type\SearchApiString;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for query type.
 *
 * @group facets
 */
class SearchApiStringTest extends UnitTestCase {

  /**
   * Tests string query type without executing the query with an "AND" operator.
   */
  public function testQueryTypeAnd() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'AND'],
      'facets_facet'
    );

    $original_results = [
      ['count' => 3, 'filter' => 'badger'],
      ['count' => 5, 'filter' => 'mushroom'],
      ['count' => 7, 'filter' => 'narwhal'],
      ['count' => 9, 'filter' => 'unicorn'],
    ];

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf('\Drupal\facets\FacetInterface', $built_facet);

    $results = $built_facet->getResults();
    $this->assertInternalType('array', $results);

    foreach ($original_results as $k => $result) {
      $this->assertInstanceOf('\Drupal\facets\Result\ResultInterface', $results[$k]);
      $this->assertEquals($result['count'], $results[$k]->getCount());
      $this->assertEquals($result['filter'], $results[$k]->getDisplayValue());
    }
  }

  /**
   * Tests string query type without executing the query with an "OR" operator.
   */
  public function testQueryTypeOr() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'OR'],
      'facets_facet'
    );

    $facet->setUnfilteredResults([
      'field_animal' => [
        ['count' => 9, 'filter' => 'unicorn'],
        ['count' => 3, 'filter' => 'badger'],
        ['count' => 7, 'filter' => 'narwhal'],
        ['count' => 5, 'filter' => 'mushroom'],
      ],
    ]);

    $facet->setFieldIdentifier('field_animal');

    $original_results = [
      ['count' => 3, 'filter' => 'badger'],
      ['count' => 5, 'filter' => 'mushroom'],
      ['count' => 7, 'filter' => 'narwhal'],
      ['count' => 9, 'filter' => 'unicorn'],
    ];

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf('\Drupal\facets\FacetInterface', $built_facet);

    $results = $built_facet->getResults();
    $this->assertInternalType('array', $results);

    foreach ($original_results as $k => $result) {
      $this->assertInstanceOf('\Drupal\facets\Result\ResultInterface', $results[$k]);
      $this->assertEquals($result['count'], $results[$k]->getCount());
      $this->assertEquals($result['filter'], $results[$k]->getDisplayValue());
    }
  }

  /**
   * Tests string query type without results.
   */
  public function testEmptyResults() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf('\Drupal\facets\FacetInterface', $built_facet);

    $results = $built_facet->getResults();
    $this->assertInternalType('array', $results);
    $this->assertEmpty($results);
  }

  /**
   * Tests string query type without results.
   */
  public function testConfiguration() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $default_config = ['facet' => $facet, 'query' => $query];
    $query_type = new SearchApiString($default_config, 'search_api_string', []);

    $this->assertEquals([], $query_type->defaultConfiguration());
    $this->assertEquals($default_config, $query_type->getConfiguration());

    $query_type->setConfiguration(['owl' => 'Long-eared owl']);
    $this->assertEquals(['owl' => 'Long-eared owl'], $query_type->getConfiguration());
  }

}

<?php

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\graphql\Entity\Server;
use Drupal\social_graphql\Wrappers\EntityEdge;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Bass class for Open Social GraphQL tests.
 *
 * Provides utility methods for testing Open Social GraphQL endpoints. Ensures
 * the Open Social GraphQL server is loaded and configured.
 */
abstract class SocialGraphQLTestBase extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    "social_graphql",
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // We need the configuration for social_graphql module to be loaded as it
    // contains the Open Social GraphQL server.
    $this->installConfig("social_graphql");

    // Set up the schema and use the Open Social GraphQL server in queries.
    $this->server = Server::load("open_social_graphql");
  }

  /**
   * Asserts that a GQL query executes correctly and matches an expected result.
   *
   * @param string $query
   *   The GraphQL query to test.
   * @param array $expected
   *   The expected output.
   * @param string $message
   *   An optional message to provide context in case of assertion failure.
   */
  protected function assertQuery(string $query, array $expected, string $message = ''): void {
    $result = $this->query($query);
    self::assertSame(200, $result->getStatusCode(), $message);
    self::assertSame($expected, json_decode($result->getContent(), TRUE), $message);
  }

  /**
   * Asserts that a specific endpoint properly implements pagination.
   *
   * Uses the standardised pagination test cases to validate that pagination
   * is implemented for the endpoint according to the Relay Connection
   * specification.
   *
   * Validation of a correct pagination selection uses validation by uuid. If
   * your endpoint produces content that does not support UUIDs then you must
   * implement your own assertions.
   *
   * @param string $endpoint
   *   The name of the GraphQL endpoint to test.
   * @param array $test_nodes
   *   The array of nodes that should be used as data source.
   * @param string $edgeClass
   *   The class used to create Edge objects from nodes (EntityEdge by
   *   default).
   */
  protected function assertEndpointSupportsPagination(string $endpoint, array $test_nodes, string $edgeClass = EntityEdge::class): void {
    foreach ($this->getPaginationTestCasesFor($test_nodes, $edgeClass) as [$filter, $result_nodes]) {
      // Create a query for the filter under test. Include some data that allow
      // verifying the results.
      $query = "
        query {
          ${endpoint}(${filter}) {
            edges {
              cursor
              node {
                uuid
              }
            }
          }
        }
      ";
      // Create our expectation array.
      $expected = [
        "data" => [
          $endpoint => [
            "edges" => array_map(
              static function ($node) use ($edgeClass) {
                return [
                  "cursor" => (new $edgeClass($node))->getCursor(),
                  "node" => [
                    "uuid" => $node->uuid(),
                  ],
                ];
              },
              $result_nodes
            ),
          ],
        ],
      ];

      $this->assertQuery($query, $expected, "${endpoint}(${filter})");
    }
  }

  /**
   * Generate standardised pagination test cases.
   *
   * Pagination should be implemented according to the Relay Connection
   * specification. This means that any endpoint that supports pagination should
   * be able to pass a standardised set of test cases.
   *
   * @param array $nodes
   *   The array of nodes that should be used as data source.
   * @param string $edgeClass
   *   The class used to create Edge objects from nodes (EntityEdge by
   *   default).
   *
   * @return \Generator
   *   Yields arrays with two values: a filter query for the GraphQL endpoint
   *   under test and the expected nodes that are selected.
   */
  protected function getPaginationTestCasesFor(array $nodes, string $edgeClass = EntityEdge::class): ?\Generator {
    assert(count($nodes) >= 10, "The array of nodes provided to " . __FUNCTION__ . " must have at least 10 entries to perform pagination testing.");

    $last = count($nodes) - 1;

    yield [
      'first: 3',
      [$nodes[0], $nodes[1], $nodes[2]],
    ];

    yield [
      "first: 3, reverse: true",
      [$nodes[$last], $nodes[$last - 1], $nodes[$last - 2]],
    ];

    yield [
      "last: 3",
      [$nodes[$last - 2], $nodes[$last - 1], $nodes[$last]],
    ];

    yield [
      "last: 3, reverse: true",
      [$nodes[2], $nodes[1], $nodes[0]],
    ];

    $cursor = (new $edgeClass($nodes[4]))->getCursor();
    yield [
      "last: 2, before: ${cursor}",
      [$nodes[2], $nodes[3]],
    ];

    yield [
      "first: 2, after: ${cursor}",
      [$nodes[5], $nodes[6]],
    ];

    yield [
      "last: 2, before: ${cursor}, reverse: true",
      [$nodes[6], $nodes[5]],
    ];

    yield [
      "first: 2, after: ${cursor}, reverse: true",
      [$nodes[3], $nodes[2]],
    ];
  }

}

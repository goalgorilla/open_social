<?php

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\graphql\Entity\Server;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use GraphQL\Server\OperationParams;

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
  protected function setUp() : void {
    parent::setUp();

    // We need the configuration for social_graphql module to be loaded as it
    // contains the Open Social GraphQL server.
    $this->installConfig("social_graphql");

    // Set up the schema and use the Open Social GraphQL server in queries.
    $this->server = Server::load("open_social_graphql");
  }

  /**
   * Asserts that a specific endpoint properly implements pagination.
   *
   * Uses the standardised pagination test cases to validate that pagination
   * is implemented for the endpoint according to the Relay Connection
   * specification.
   *
   * @param string $endpoint
   *   The name of the GraphQL endpoint to test.
   * @param array $uuids
   *   The array of uuids of all source data for the endpoint in the
   *   non-reversed sorted order.
   * @param string|null $sortKey
   *   The GraphQL value of the sortKey field in the query or NULL to use the
   *   default sort order.
   * @param array $parents
   *   The parent fields from the query root to the pagination field. Can
   *   contain filters (e.g. `['user(id: "0000-001")', 'friend']`).
   */
  protected function assertEndpointSupportsPagination(string $endpoint, array $uuids, string $sortKey = NULL, array $parents = []): void {
    assert(count($uuids) >= 10, "The array of uuids provided to " . __FUNCTION__ . " must have at least 10 entries to perform pagination testing.");

    $last = count($uuids) - 1;

    $this->assertPaginationPage(
      $endpoint,
      'first',
      NULL,
      NULL,
      $sortKey,
      TRUE,
      FALSE,
      $parents,
      [$uuids[0], $uuids[1], $uuids[2]],
      [$uuids[3], $uuids[4]],
    );

    $this->assertPaginationPage(
      $endpoint,
      'first',
      TRUE,
      NULL,
      $sortKey,
      TRUE,
      FALSE,
      $parents,
      [$uuids[$last], $uuids[$last - 1], $uuids[$last - 2]],
      [$uuids[$last - 3], $uuids[$last - 4]],
    );

    $this->assertPaginationPage(
      $endpoint,
      'last',
      NULL,
      NULL,
      $sortKey,
      FALSE,
      TRUE,
      $parents,
      [$uuids[$last - 2], $uuids[$last - 1], $uuids[$last]],
      [$uuids[$last - 4], $uuids[$last - 3]],
    );

    $this->assertPaginationPage(
      $endpoint,
      'last',
      TRUE,
      NULL,
      $sortKey,
      FALSE,
      TRUE,
      $parents,
      [$uuids[2], $uuids[1], $uuids[0]],
      [$uuids[4], $uuids[3]],
    );
  }

  /**
   * Asserts a single page of results of an endpoint for the given inputs.
   *
   * @param string $field
   *   The field to test.
   * @param string $side
   *   The side of the data to test. Either 'first' or 'last'.
   * @param bool|null $reverse
   *   Whether the request is reversed.
   * @param string|null $cursor
   *   A cursor to use as offset in the form of 'before: "cursor"' or
   *   'after: "cursor"'.
   * @param string|null $sortKey
   *   The value for the sortKey or null to use the default value.
   * @param bool $hasNextPage
   *   The expected pageInfo.hasNextPage value.
   * @param bool $hasPreviousPage
   *   The expected pageInfo.hasPreviousPage value.
   * @param array $parents
   *   The parent fields from the query root to the pagination field. Can
   *   contain filters (e.g. `['user(id: "0000-001")', 'friend']`).
   * @param array $first_page
   *   The uuids that are expected to be in the page's results.
   * @param array|null $second_page
   *   A second page of results that is expected. If provided the first request
   *   will use a cursor based on $side to assert a second request with this
   *   array as $result_nodes.
   */
  protected function assertPaginationPage(string $field, string $side, ?bool $reverse, ?string $cursor, ?string $sortKey, bool $hasNextPage, bool $hasPreviousPage, array $parents, array $first_page, array $second_page = NULL): void {
    $count = count($first_page);
    // Construct the filter as a string. We do this instead of variables since
    // it makes our function signature a bit easier.
    $filter = "${side}: ${count}";
    if ($reverse) {
      $filter .= ", reverse: true";
    }
    if ($cursor) {
      $filter .= ", ${cursor}";
    }
    if ($sortKey) {
      $filter .= ", sortKey: ${sortKey}";
    }

    // Create a query for the filter under test. Include some data that allow
    // verifying the results.
    $open_path = empty($parents) ? "" : implode(" {", $parents) . " { ";
    $close_path = empty($parents) ? "" : " } " . implode("}", array_map(static fn () => "", $parents));
    $query = "
        query {
          ${open_path}
          ${field}(${filter}) {
            pageInfo {
              hasPreviousPage
              hasNextPage
              startCursor
              endCursor
            }
            nodes {
              id
            }
            edges {
              cursor
              node {
                id
              }
            }
          }
          ${close_path}
        }

      ";

    $executionResult = $this->server->executeOperation(
      OperationParams::create([
        'query' => $query,
      ])
    );

    // If an exception was thrown during execution then we re-throw the
    // exception here since a developer's next step after such a failing test is
    // almost always to find the location of the error. This makes debugging
    // test failures a lot easier.
    if (!empty($executionResult->extensions)) {
      foreach ($executionResult->extensions as $maybeException) {
        if ($maybeException instanceof \Throwable) {
          throw $maybeException;
        }
      }
    }

    // Matching to an empty array causes a diff to show more information to
    // developers than simply an assertEmpty check.
    self::assertEquals([], $executionResult->errors, "Errors for ${open_path}${field}(${filter})${close_path}");
    self::assertNotNull($executionResult->data, "No data for ${open_path}${field}(${filter})${close_path}");

    $parent_fields = array_map(static fn ($f) => explode('(', $f)[0], $parents);
    $data = NestedArray::getValue($executionResult->data, [...$parent_fields, $field]);
    self::assertNotNull($data, "No data for ${open_path}${field}(${filter})${close_path}");

    $startCursor = $data['edges'][0]['cursor'];
    $endCursor = $data['edges'][count($first_page) - 1]['cursor'];

    $expected_page_info = [
      'hasPreviousPage' => $hasPreviousPage,
      'hasNextPage' => $hasNextPage,
      'startCursor' => $startCursor,
      'endCursor' => $endCursor,
    ];

    self::assertNotEquals(NULL, $data['pageInfo']['startCursor'], "Missing startCursor for ${field}(${filter})");
    self::assertNotEquals(NULL, $data['pageInfo']['endCursor'], "Missing endCursor for ${field}(${filter})");
    self::assertEquals($expected_page_info, $data['pageInfo'], "Incorrect pageInfo for ${field}(${filter})");

    $expected_nodes = array_map(
      static fn ($uuid) => ['id' => $uuid],
      $first_page
    );

    self::assertEquals($expected_nodes, $data['nodes'], "Incorrect nodes for ${field}(${filter})");

    // The cursor is ignored as it's an implementation detail and we have no
    // good way of predicting its value for comparison. It's usefulness is
    // tested using the $second_page in a test.
    $expected_edge_data = array_map(
      static fn ($uuid) => ['node' => ['id' => $uuid]],
      $first_page
    );
    $actual_edge_data = array_map(
      static function ($edge) {
        // Unset the cursor instead of mapping to an array with only the edge to
        // ensure that our test fails if more data than a node and cursor was
        // provided.
        unset($edge['cursor']);
        return $edge;
      },
      $data['edges']
    );

    self::assertEquals($expected_edge_data, $actual_edge_data, "Incorrect edges for ${field}(${filter}) (cursors omitted from check)");

    // If we've been given a second page then we can test with a cursor.
    if (!empty($second_page)) {
      $cursor = $side === 'first' ? "after: \"${endCursor}\"" : "before: \"${startCursor}\"";
      $this->assertPaginationPage(
        $field,
        $side,
        $reverse,
        $cursor,
        $sortKey,
        TRUE,
        TRUE,
        $parents,
        $second_page,
      );
    }
  }

}

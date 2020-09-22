<?php

namespace Drupal\Tests\social_graphql\Unit;

use Drupal\social_graphql\Wrappers\EntityConnection;
use Drupal\social_graphql\Wrappers\EntityEdge;
use Drupal\Tests\UnitTestCase;
use GraphQL\Deferred;

/**
 * @coversDefaultClass \Drupal\social_graphql\Wrappers\EntityConnection
 * @group EntityConnection
 * @group social_graphql
 */
class EntityConnectionTest extends UnitTestCase {

  /**
   * Tests that PageInfo is correctly generated for data sets.
   *
   * @covers ::pageInfo
   * @dataProvider pageInfoTestData
   */
  public function testPageInfo($description, EntityConnection $connection, $expectation) {
    $promise = $connection->pageInfo();
    $promise::runQueue();
    self::assertEquals($expectation, $promise->result, $description);
  }

  /**
   * Provides test scenarios for the testPageInfo method.
   *
   * @return array[]
   *   Test data for the pageInfo method.
   */
  public function pageInfoTestData() {
    // Create a bunch of mock edges as our data set. We can provide filtered
    // subsets of this to the connection with various configurations.
    $_ = [
      $this->createMockEntityEdge(),
      $this->createMockEntityEdge(),
      $this->createMockEntityEdge(),
      $this->createMockEntityEdge(),
      $this->createMockEntityEdge(),
    ];

    // Make a reference to the last edge so we can do reverse based indexing.
    $end = count($_) - 1;

    return [
      [
        "An empty result should provide empty Page Info data.",
        new EntityConnection(
          $this->deferValue([]),
          5, NULL, NULL, NULL, FALSE
        ),
        [
          'hasNextPage' => FALSE,
          'hasPreviousPage' => FALSE,
          'startCursor' => NULL,
          'endCursor' => NULL,
        ],
      ],
      [
        "Fetching the first 3 results while receiving more shows a next page but not a previous page.",
        new EntityConnection(
          $this->deferValue([$_[0], $_[1], $_[2], $_[3]]),
          3, NULL, NULL, NULL, FALSE
        ),
        [
          'hasNextPage' => TRUE,
          'hasPreviousPage' => FALSE,
          'startCursor' => $_[0]->getCursor(),
          'endCursor' => $_[2]->getCursor(),
        ],
      ],
      [
        "Fetching the first 3 results while receiving more after a cursor shows a next page and a previous page.",
        new EntityConnection(
          $this->deferValue([$_[1], $_[2], $_[3], $_[4]]),
          3, $_[0]->getCursor(), NULL, NULL, FALSE
        ),
        [
          'hasNextPage' => TRUE,
          'hasPreviousPage' => TRUE,
          'startCursor' => $_[1]->getCursor(),
          'endCursor' => $_[3]->getCursor(),
        ],
      ],
      [
        "Fetching the last 3 results while receiving more shows a previous page but not a next page.",
        new EntityConnection(
          $this->deferValue([$_[$end], $_[$end - 1], $_[$end - 2], $_[$end - 3]]),
          NULL, NULL, 3, NULL, TRUE
        ),
        [
          'hasNextPage' => FALSE,
          'hasPreviousPage' => TRUE,
          'startCursor' => $_[$end]->getCursor(),
          'endCursor' => $_[$end - 2]->getCursor(),
        ],
      ],
      [
        "Fetching the last 3 results while receiving more before a cursor shows a next page and a previous page.",
        new EntityConnection(
          $this->deferValue(
            [$_[$end - 1], $_[$end - 2], $_[$end - 3], $_[$end - 4]]
          ),
          NULL, NULL, 3, $_[$end]->getCursor(), TRUE
        ),
        [
          'hasNextPage' => TRUE,
          'hasPreviousPage' => TRUE,
          'startCursor' => $_[$end - 1]->getCursor(),
          'endCursor' => $_[$end - 3]->getCursor(),
        ],
      ],
    ];
  }

  /**
   * Tests that edges are correctly generated for data sets.
   *
   * @covers ::edges
   * @dataProvider edgesTestData
   */
  public function testEdges($description, EntityConnection $connection, $expectation) {
    $promise = $connection->edges();
    $promise::runQueue();
    self::assertEquals($expectation, $promise->result, $description);
  }

  /**
   * Provides test scenarios for the testEdges method.
   *
   * @return array[]
   *   Test data for the edges method.
   */
  public function edgesTestData() {
    // Create a bunch of mock edges as our data set. We can provide filtered
    // subsets of this to the connection with various configurations.
    $_ = [
      $this->createMockEntityEdge(),
      $this->createMockEntityEdge(),
      $this->createMockEntityEdge(),
      $this->createMockEntityEdge(),
      $this->createMockEntityEdge(),
    ];

    // Make a reference to the last edge so we can do reverse based indexing.
    $end = count($_) - 1;

    return [
      [
        "An empty result should provide empty an empty array.",
        new EntityConnection(
          $this->deferValue([]),
          5, NULL, NULL, NULL, FALSE
        ),
        [],
      ],
      [
        "Fetching the first 3 results while receiving more correctly applies the limit.",
        new EntityConnection(
          $this->deferValue([$_[0], $_[1], $_[2], $_[3]]),
          3, NULL, NULL, NULL, FALSE
        ),
        [$_[0], $_[1], $_[2]],
      ],
      [
        "Fetching the last 3 results while receiving more correctly applies the limit and reverses the order.",
        new EntityConnection(
          $this->deferValue([$_[$end], $_[$end - 1], $_[$end - 2], $_[$end - 3]]),
          NULL, NULL, 3, NULL, TRUE
        ),
        [$_[$end - 2], $_[$end - 1], $_[$end]],
      ],
    ];
  }

  /**
   * Transforms a value into a Promise object.
   *
   * @param mixed $value
   *   The value to return.
   *
   * @return \GraphQL\Deferred
   *   An instance of the GraphQL Deferred class that resolves to the provided
   *   value.
   */
  protected function deferValue($value) {
    return new Deferred(function () use ($value) {
      return $value;
    });
  }

  /**
   * Creates a mock entity edge which can be used in the connection.
   */
  protected function createMockEntityEdge() {
    $stubEdge = $this->createMock(EntityEdge::class);
    $stubEdge->method('getCursor')
      ->willReturn($this->createFakeCursor());
    return $stubEdge;
  }

  /**
   * Creates a fake cursor that can be used for pagination.
   */
  protected function createFakeCursor() {
    return base64_encode(random_bytes(5));
  }

}

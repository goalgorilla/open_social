<?php

namespace Drupal\social_graphql\GraphQL;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\social_graphql\Wrappers\Cursor;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Provides an interface for a connection query helper.
 *
 * A connection query helper provides an EntityConnection implementation with
 * the data that it needs to fetch data on the connection in a specific
 * configuration.
 */
interface ConnectionQueryHelperInterface {

  /**
   * Get the query that's at the root of this connection.
   *
   * This is a good place to apply any filtering that has been provided by the
   * client.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query or aggregate entity query.
   */
  public function getQuery() : QueryInterface;

  /**
   * Returns a cursor object for a given cursor string.
   *
   * @param string $cursor
   *   A cursor string created obtained from an edge for the connection.
   *
   * @return \Drupal\social_graphql\Wrappers\Cursor|null
   *   An object with the cursor information or null if it was an invalid
   *   cursor.
   */
  public function getCursorObject(string $cursor) : ?Cursor;

  /**
   * Returns the name of the ID field of this query.
   *
   * The ID field is used as fallback in case entities have the same value for
   * the sort field. This ensures a stable sort in all cases.
   *
   * @return string
   *   The query field name to use as ID.
   */
  public function getIdField() : string;

  /**
   * Returns the name of the field to use for sorting this connection.
   *
   * The cursor value will be used with this field.
   *
   * @return string
   *   The sort field name.
   */
  public function getSortField() : string;

  /**
   * The function to use for aggregate sorting.
   *
   * @return string|null
   *   The aggregate sort function or NULL if aggregate sorting shouldn't be
   *   used.
   *
   * @see \Drupal\Core\Entity\Query\QueryAggregateInterface::sortAggregate
   *
   * @todo Move this to a separate interface.
   */
  public function getAggregateSortFunction() : ?string;

  /**
   * Asynchronously turn the entity query result into edges.
   *
   * This can be used to process the results from the entity query and load them
   * using something like the GraphQL Entity Buffer. Transformative work should
   * be moved into the promise as much as possible.
   *
   * @param array $result
   *   The result of the entity query as started in getQuery.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   A promise that resolves into the edges for this connection.
   */
  public function getLoaderPromise(array $result) : SyncPromise;

}

<?php

namespace Drupal\social_graphql\Wrappers;

use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Default implementation for connections for entities.
 */
class EntityConnection implements ConnectionInterface {

  /**
   * Fetch the first N results.
   *
   * @var int|null
   */
  protected $first;

  /**
   * Fetch the last N results.
   *
   * @var int|null
   */
  protected $last;

  /**
   * The cursor that results were fetched after.
   *
   * @var string|null
   */
  protected $after;

  /**
   * The cursor that results were fetched before.
   *
   * @var string|null
   */
  protected $before;

  /**
   * Whether the results provided to us are in reversed order.
   *
   * @var bool
   */
  protected $isReversed;

  /**
   * The result-set of this connection.
   *
   * @var \GraphQL\Executor\Promise\Adapter\SyncPromise
   */
  protected $result;

  /**
   * QueryConnection constructor.
   *
   * @param \GraphQL\Executor\Promise\Adapter\SyncPromise $promise
   * @param int|null $first
   * @param string|null $after
   * @param int|null $last
   * @param string|null $before
   * @param bool $is_reversed
   */
  public function __construct(SyncPromise $promise, ?int $first, ?string $after, ?int $last, ?string $before, bool $is_reversed) {
    // If a connection is created where we have a first and last that are null
    // or a first and last that are specified then the values have not been
    // validated. This is a developer error.
    if (is_null($first) === is_null($last)) {
      throw new \RuntimeException("Either `first` XOR `last` must be specified for an EntityQueryConnection.");
    }

    $this->result = $promise;
    $this->first = $first;
    $this->after = $after;
    $this->last = $last;
    $this->before = $before;
    $this->isReversed = $is_reversed;
  }

  /**
   * {@inheritDoc}
   */
  public function pageInfo() {
    return $this->result->then(function ($edges) {
      /** @var \Drupal\social_graphql\Wrappers\EntityEdge[] $edges */
      // If we don't have any results then we won't have any other pages either.
      if (empty($edges)) {
        return [
          'hasNextPage' => FALSE,
          'hasPreviousPage' => FALSE,
          'startCursor' => NULL,
          'endCursor' => NULL,
        ];
      }

      // Count the number of elements that we have so we can check if we have
      // future pages.
      $count = count($edges);
      // The last item is either based on the limit or on the number of fetched
      // items if it's below the limit. Correct for 0 based indexing.
      $last_index = min($this->first ?? $this->last, $count) - 1;

      return [
        // We have a next page if the before cursor was provided (we assume
        // calling code has validated the cursor) or if N first results were
        // requested and we have more.
        'hasNextPage' => $this->before !== NULL || ($this->first !== NULL && $this->first < $count),
        // We have a previous page if the after cursor was provided (we assume
        // calling code has validated the cursor) or if N last results were
        // requested and we have more.
        'hasPreviousPage' => $this->after !== NULL || ($this->last !== NULL && $this->last < $count),
        // The start cursor is always the first cursor in the result-set
        // independent of the result-set order.
        'startCursor' => $edges[0]->getCursor(),
        // The end cursor is always the last cursor in the result-set
        // independent of the result-set order.
        'endCursor' => $edges[$last_index]->getCursor(),
      ];
    });
  }

  /**
   * {@inheritDoc}
   */
  public function edges() {
    return $this->result->then(function ($edges) {
      // To allow for pagination we over-fetch results by one above the limits
      // so we must fix that now.
      $edges = array_slice($edges, 0, $this->first ?? $this->last);

      // If the pagination caused the results to be reversed then we must swap
      // them around to get the requested order.
      // See QueryEntityBase::applyPagination().
      if ($this->isReversed) {
        $edges = array_reverse($edges);
      }

      return $edges;
    });
  }

}

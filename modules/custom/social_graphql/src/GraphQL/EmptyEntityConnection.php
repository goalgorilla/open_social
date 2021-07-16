<?php

declare(strict_types=1);

namespace Drupal\social_graphql\GraphQL;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Provides an entity connection when it's known there are no results.
 *
 * @package Drupal\social_graphql\GraphQL
 */
class EmptyEntityConnection implements ConnectionInterface {

  /**
   * {@inheritdoc}
   */
  public function setPagination(?int $first, ?string $after, ?int $last, ?string $before, bool $reverse): ConnectionInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function pageInfo(): SyncPromise {
    return new Deferred(
      fn () => [
        'hasNextPage' => FALSE,
        'hasPreviousPage' => FALSE,
        'startCursor' => NULL,
        'endCursor' => NULL,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function edges(): SyncPromise {
    return new Deferred(fn () => []);
  }

  /**
   * {@inheritdoc}
   */
  public function nodes(): SyncPromise {
    return new Deferred(fn () => []);
  }

}

<?php

namespace Drupal\social_graphql\Wrappers;

/**
 * Provides the interface for connections.
 */
interface ConnectionInterface {

  /**
   * @return PageInfo
   */
  public function pageInfo();

  /**
   * @return EdgeInterface[]|\GraphQL\Deferred
   */
  public function edges();
}

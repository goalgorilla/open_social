<?php

namespace Drupal\social_graphql\Wrappers;

/**
 * Provides the interface for connections.
 */
interface ConnectionInterface {

  /**
   * Get the page info from the connection.
   *
   * @return array
   *   An array containing the fields of page info.
   */
  public function pageInfo();

  /**
   * Get the edges from the connection.
   *
   * @return \GraphQL\Deferred
   *   A promise that resolves to an array of EntityEdge instances.
   */
  public function edges();

}

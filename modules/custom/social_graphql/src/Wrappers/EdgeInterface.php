<?php

namespace Drupal\social_graphql\Wrappers;

/**
 * Provides a common interface for edges that DataProducers can work with.
 */
interface EdgeInterface {

  /**
   * Return the cursor for the node associated with this edge.
   */
  public function getCursor();

  /**
   * Return the node for associated with this edge.
   */
  public function getNode();

}

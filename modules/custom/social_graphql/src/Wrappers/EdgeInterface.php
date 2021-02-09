<?php

namespace Drupal\social_graphql\Wrappers;

/**
 * Provides a common interface for edges that DataProducers can work with.
 */
interface EdgeInterface {

  /**
   * Return the cursor for this edge.
   */
  public function getCursor() : string;

  /**
   * Return the node for this edge.
   */
  public function getNode();

}

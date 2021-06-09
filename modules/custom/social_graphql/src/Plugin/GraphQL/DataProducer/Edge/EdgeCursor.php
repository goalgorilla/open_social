<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Edge;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\EdgeInterface;

/**
 * Returns the cursor for an edge.
 *
 * @DataProducer(
 *   id = "edge_cursor",
 *   name = @Translation("Edge cursor"),
 *   description = @Translation("Returns the cursor of an edge."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Cursor")
 *   ),
 *   consumes = {
 *     "edge" = @ContextDefinition("any",
 *       label = @Translation("EdgeInterface")
 *     )
 *   }
 * )
 */
class EdgeCursor extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value for this data producer.
   *
   * @param \Drupal\social_graphql\Wrappers\EdgeInterface $edge
   *   The edge to return the cursor for.
   *
   * @return mixed
   *   The cursor for this edge.
   */
  public function resolve(EdgeInterface $edge) {
    return $edge->getCursor();
  }

}

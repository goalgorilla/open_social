<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\EdgeInterface;

/**
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
   * @param \Drupal\social_graphql\Wrappers\EdgeInterface $edge
   *
   * @return mixed
   */
  public function resolve(EdgeInterface $edge) {
    return $edge->getCursor();
  }

}

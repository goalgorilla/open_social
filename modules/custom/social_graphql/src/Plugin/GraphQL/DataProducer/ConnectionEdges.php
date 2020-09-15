<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\EntityConnection;

/**
 * @DataProducer(
 *   id = "connection_edges",
 *   name = @Translation("Connection edges"),
 *   description = @Translation("Returns the edges of a connection."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Edges")
 *   ),
 *   consumes = {
 *     "connection" = @ContextDefinition("any",
 *       label = @Translation("QueryConnection")
 *     )
 *   }
 * )
 */
class ConnectionEdges extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * @param \Drupal\social_graphql\Wrappers\EntityConnection $connection
   *
   * @return mixed
   */
  public function resolve(EntityConnection $connection) {
    return $connection->edges();
  }

}

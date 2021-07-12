<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Connection;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\GraphQL\ConnectionInterface;
use Drupal\social_graphql\GraphQL\EntityConnection;

/**
 * Produces the edges from a connection object.
 *
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
   * Resolves the request.
   *
   * @param \Drupal\social_graphql\GraphQL\ConnectionInterface $connection
   *   The connection to return the edges from.
   *
   * @return mixed
   *   The edges for the connection.
   */
  public function resolve(ConnectionInterface $connection) {
    return $connection->edges();
  }

}

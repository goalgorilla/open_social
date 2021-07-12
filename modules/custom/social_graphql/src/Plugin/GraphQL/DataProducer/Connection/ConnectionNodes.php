<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Connection;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\GraphQL\ConnectionInterface;

/**
 * Produces the edges from a connection object.
 *
 * @DataProducer(
 *   id = "connection_nodes",
 *   name = @Translation("Connection nodes"),
 *   description = @Translation("Returns the nodes of a connection."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Nodes")
 *   ),
 *   consumes = {
 *     "connection" = @ContextDefinition("any",
 *       label = @Translation("EntityConnection")
 *     )
 *   }
 * )
 */
class ConnectionNodes extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

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
    return $connection->nodes();
  }

}

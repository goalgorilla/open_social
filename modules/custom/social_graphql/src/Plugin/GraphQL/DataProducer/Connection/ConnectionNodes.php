<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Connection;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
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
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   The nodes for the connection.
   *
   * @throws \Exception
   */
  public function resolve(ConnectionInterface $connection, RefinableCacheableDependencyInterface $metadata): SyncPromise {
    $nodes = $connection->nodes();
    // The metadata is calculated only once the EntityConnection::execute
    // happens. Now, we fetch the metadata from it and merge with current
    // cacheability metadata.
    $metadata->addCacheableDependency($connection->getMetadata());
    return $nodes;
  }

}

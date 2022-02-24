<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Connection;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\GraphQL\ConnectionInterface;

/**
 * Produces the page info from a connection object.
 *
 * @DataProducer(
 *   id = "connection_page_info",
 *   name = @Translation("Connection page info"),
 *   description = @Translation("Returns the page info of a connection."),
 *   produces = @ContextDefinition("page_info",
 *     label = @Translation("Page Info")
 *   ),
 *   consumes = {
 *     "connection" = @ContextDefinition("any",
 *       label = @Translation("QueryConnection")
 *     )
 *   }
 * )
 */
class ConnectionPageInfo extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the request.
   *
   * @param \Drupal\social_graphql\GraphQL\ConnectionInterface $connection
   *   The connection to return the edges from.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return mixed
   *   The page info for the connection.
   *
   * @throws \Exception
   */
  public function resolve(ConnectionInterface $connection, RefinableCacheableDependencyInterface $metadata) {
    $pageInfo = $connection->pageInfo();
    // The metadata is calculated only once the EntityConnection::execute
    // happens. Now, we fetch the metadata from it and merge with current
    // cacheability metadata.
    $metadata->addCacheableDependency($connection->getMetadata());
    return $pageInfo;
  }

}

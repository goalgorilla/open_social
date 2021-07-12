<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Connection;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\GraphQL\ConnectionInterface;
use Drupal\social_graphql\GraphQL\EntityConnection;

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
   *   The connection to return the page info for.
   *
   * @return mixed
   *   The page info for the connection.
   */
  public function resolve(ConnectionInterface $connection) {
    return $connection->pageInfo();
  }

}

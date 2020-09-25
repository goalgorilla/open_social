<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Connection;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\EntityConnection;

/**
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
   * @param \Drupal\social_graphql\Wrappers\EntityConnection $connection
   *
   * @return mixed
   */
  public function resolve(EntityConnection $connection) {
    return $connection->pageInfo();
  }

}

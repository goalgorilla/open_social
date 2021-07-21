<?php

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\social_graphql\GraphQL\EntityConnection;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;
use Drupal\social_topic\Plugin\GraphQL\QueryHelper\TopicQueryByTypeHelper;

/**
 * Queries the topics by type on the platform.
 *
 * @DataProducer(
 *   id = "query_topic_by_type",
 *   name = @Translation("Query a list of topics"),
 *   description = @Translation("Loads the topics."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("EntityConnection")
 *   ),
 *   consumes = {
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Unique identifier of topic type"),
 *       required = TRUE
 *     ),
 *     "first" = @ContextDefinition("integer",
 *       label = @Translation("First"),
 *       required = FALSE
 *     ),
 *     "after" = @ContextDefinition("string",
 *       label = @Translation("After"),
 *       required = FALSE
 *     ),
 *     "last" = @ContextDefinition("integer",
 *       label = @Translation("Last"),
 *       required = FALSE
 *     ),
 *     "before" = @ContextDefinition("string",
 *       label = @Translation("Before"),
 *       required = FALSE
 *     ),
 *     "reverse" = @ContextDefinition("boolean",
 *       label = @Translation("Reverse"),
 *       required = FALSE,
 *       default_value = FALSE
 *     ),
 *     "sortKey" = @ContextDefinition("string",
 *       label = @Translation("Sort key"),
 *       required = FALSE,
 *       default_value = "CREATED_AT"
 *     ),
 *   }
 * )
 */
class QueryTopicByType extends EntityDataProducerPluginBase {

  /**
   * Resolves the request to the requested values.
   *
   * @param string $type
   *   The topic type ID.
   * @param int|null $first
   *   Fetch the first X results.
   * @param string|null $after
   *   Cursor to fetch results after.
   * @param int|null $last
   *   Fetch the last X results.
   * @param string|null $before
   *   Cursor to fetch results before.
   * @param bool $reverse
   *   Reverses the order of the data.
   * @param string $sortKey
   *   Key to sort by.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return \Drupal\social_graphql\GraphQL\ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(string $type, ?int $first, ?string $after, ?int $last, ?string $before, bool $reverse, string $sortKey, RefinableCacheableDependencyInterface $metadata) {
    $query_helper = new TopicQueryByTypeHelper($sortKey, $this->entityTypeManager, $this->graphqlEntityBuffer, $type);
    $metadata->addCacheableDependency($query_helper);

    $connection = new EntityConnection($query_helper);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    return $connection;
  }

}

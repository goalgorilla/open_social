<?php

namespace Drupal\social_user\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\QueryEntityBase;

/**
 * Queries the users on the platform.
 *
 * @DataProducer(
 *   id = "query_user",
 *   name = @Translation("Query a list of users"),
 *   description = @Translation("Retrieves a list of teas."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("User connection")
 *   ),
 *   consumes = {
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
class QueryUser extends QueryEntityBase {

  /**
   * The highest limit a client is allowed to specify.
   */
  const MAX_LIMIT = 100;

  /**
   * Resolves the request to the requested values.
   *
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
   * @return \Drupal\social_graphql\Wrappers\EntityConnection
   *   An entity connection with results and data about the paginated results.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(?int $first, ?string $after, ?int $last, ?string $before, bool $reverse, string $sortKey, RefinableCacheableDependencyInterface $metadata) {
    $storage = $this->entityTypeManager->getStorage('user');
    $type = $storage->getEntityType();
    $query = $storage->getQuery()
      ->currentRevision()
      ->accessCheck();

    // Exclude the anonymous user from listings because it doesn't make sense
    // in overview pages.
    $query->condition('uid', 0, '!=');

    $metadata->addCacheTags($type->getListCacheTags());
    $metadata->addCacheContexts($type->getListCacheContexts());

    return $this->resolvePaginatedQuery($query, $first, $after, $last, $before, $reverse, $sortKey);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSortField(string $sortKey) : string {
    switch ($sortKey) {
      case 'FIRST_NAME':
        return 'field_first_name';

      case 'LAST_NAME':
        return 'field_last_name';

      case 'CREATED_AT':
        return 'created';

      default:
        throw new \InvalidArgumentException("Unsupported sortKey for sorting '${$sortKey}'");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityValue(EntityInterface $entity, string $sortKey) {
    switch ($sortKey) {
//      case 'FIRST_NAME':
//        // TODO: Profile data not available for users.
//        break;
//      case 'LAST_NAME':
//        // TODO: Profile data not available for users.
//        break;
      case 'CREATED_AT':
        return $entity->getCreatedTime();

      default:
        throw new \InvalidArgumentException("Unsupported sortKey for pagination '${$sortKey}'");
    }
  }

}

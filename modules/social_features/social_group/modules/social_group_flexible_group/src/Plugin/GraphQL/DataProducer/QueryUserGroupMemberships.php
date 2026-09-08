<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\social_graphql\GraphQL\EntityConnection;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;
use Drupal\social_group_flexible_group\Plugin\GraphQL\QueryHelper\UserGroupMembershipsQueryHelper;
use Drupal\user\UserInterface;

/**
 * Queries group memberships for a user.
 *
 * @DataProducer(
 *   id = "query_user_group_memberships",
 *   name = @Translation("User group memberships"),
 *   description = @Translation("Loads paginated group memberships for a user."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Group membership connection")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
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
 *       default_value = "JOINED_AT"
 *     ),
 *   }
 * )
 */
final class QueryUserGroupMemberships extends EntityDataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Resolves the request to the requested values.
   *
   * @return \Drupal\social_graphql\GraphQL\ConnectionInterface
   *   A paginated group membership connection.
   */
  public function resolve(UserInterface $entity, ?int $first, ?string $after, ?int $last, ?string $before, bool $reverse, string $sortKey, RefinableCacheableDependencyInterface $metadata) {
    $metadata->addCacheTags([
      "group_content_list:plugin:group_membership:entity:{$entity->id()}",
    ]);
    $metadata->addCacheableDependency($entity);
    $metadata->addCacheContexts(['user', 'user.group_permissions']);

    $query_helper = new UserGroupMembershipsQueryHelper($sortKey, $this->entityTypeManager, $this->graphqlEntityBuffer, $entity);
    $metadata->addCacheableDependency($query_helper);

    $connection = new EntityConnection($query_helper);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    return $connection;
  }

}

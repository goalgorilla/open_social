<?php

namespace Drupal\social_group_flexible_group\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves the number of existing flexibleGroupMemberships for the user.
 *
 * @DataProducer(
 *   id = "social_group_flexible_group_memberships_created",
 *   name = @Translation("User number of existing flexibleGroupMemberships"),
 *   description = @Translation("The number of existing flexibleGroupMemberships that the user created."),
 *   produces = @ContextDefinition("integer",
 *     label = @Translation("EntityConnection")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     ),
 *   }
 * )
 */
class UserFlexibleGroupMemberships extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  const string CID_BASE = 'social_group_flexible_group:memberships_created:';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected Connection $database,
    protected CacheBackendInterface $cacheBackend,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('cache.default')
    );
  }

  /**
   * Resolves the request to the requested values.
   */
  public function resolve(EntityInterface $entity, RefinableCacheableDependencyInterface $metadata): int {
    $user_id = $entity->id();
    $cid = self::CID_BASE . $user_id;

    // Check if the result is already cached.
    if ($cache_data = $this->cacheBackend->get($cid)) {
      return (int) $cache_data->data;
    }

    // Get all memberships for the given user but only for flexible groups.
    // Query is similar to SocialGroupHelperService::getAllGroupsForUser.
    $query = $this->database->select('group_relationship_field_data', 'gcfd');
    $query->addField('gcfd', 'gid');
    $query->condition('gcfd.entity_id', $entity->id());
    $query->condition('gcfd.type', 'flexible_group-group_membership', 'IN');

    $result = $query
      ->countQuery()
      ->execute();

    // Calculate the result.
    // Cast to int to satisfy the user GraphQL interface.
    $result = (int) $result?->fetchField();

    // Cache the result.
    $this->cacheBackend->set($cid, $result, Cache::PERMANENT, [$cid]);

    return $result;
  }

}

<?php

namespace Drupal\social_post\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves the number of posts that the user has created on the platform.
 *
 * @DataProducer(
 *   id = "social_post_posts_created",
 *   name = @Translation("User posts created"),
 *   description = @Translation("The number of posts that the user created."),
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
class UserPostsCreated extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  const string CID_BASE = 'social_post:user_posts_created:';

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
      $container->get('cache.default'),
    );
  }

  /**
   * Resolves the request to the requested values.
   */
  public function resolve(EntityInterface $entity, RefinableCacheableDependencyInterface $metadata): int {
    // The code is copy/paste of 'user_posts_created' user export plugin.
    // Get posts count for the user.
    $user_id = $entity->id();
    $cid = self::CID_BASE . $user_id;

    // Check if the result is already cached.
    if ($cache_data = $this->cacheBackend->get($cid)) {
      return (int) $cache_data->data;
    }

    $query = $this->database->select('post', 'p');
    $query->join('post_field_data', 'pfd', 'pfd.id = p.id');
    $query->condition('pfd.user_id', (string) $user_id);

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

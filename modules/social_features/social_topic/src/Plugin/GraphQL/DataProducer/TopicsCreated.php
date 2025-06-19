<?php

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves the number of topics that the user has created on the platform.
 *
 * @DataProducer(
 *   id = "social_topics_created",
 *   name = @Translation("User topics created"),
 *   description = @Translation("The number of topics that the user created."),
 *   produces = @ContextDefinition("integer",
 *     label = @Translation("EntityConnection")),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     ),
 *   }
 * )
 */
class TopicsCreated extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  const string CID_BASE = 'social_topic:user_topics_created:';

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
    $user_id = $entity->id();
    $cid = self::CID_BASE . $user_id;

    // Check if the result is already cached.
    if ($cache_data = $this->cacheBackend->get($cid)) {
      return (int) $cache_data->data;
    }

    // The query is copy/paste of 'user_topics_created' user export plugin.
    // Get discussions count for the user.
    $query = $this->database->select('node', 'n');
    $query->join('node_field_data', 'nfd', 'nfd.nid = n.nid');
    $query
      ->condition('nfd.uid', (string) $user_id)
      ->condition('nfd.type', 'topic');

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

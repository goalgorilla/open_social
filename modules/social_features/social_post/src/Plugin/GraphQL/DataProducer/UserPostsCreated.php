<?php

namespace Drupal\social_post\Plugin\GraphQL\DataProducer;

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

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected Connection $database,
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
    );
  }

  /**
   * Resolves the request to the requested values.
   */
  public function resolve(EntityInterface $entity, RefinableCacheableDependencyInterface $metadata): int {
    $metadata->addCacheTags(['post_list']);

    // The code is copy/paste of 'user_posts_created' user export plugin.
    // Get posts count for the user.
    $user_id = $entity->id();

    $query = $this->database->select('post', 'p');
    $query->join('post_field_data', 'pfd', 'pfd.id = p.id');
    $query->condition('pfd.user_id', (string) $user_id);

    $result = $query
      ->countQuery()
      ->execute();

    // Calculate the result.
    // Cast to int to satisfy the user GraphQL interface.
    return (int) $result?->fetchField();
  }

}

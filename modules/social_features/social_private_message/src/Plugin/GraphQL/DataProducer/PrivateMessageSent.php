<?php

namespace Drupal\social_private_message\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves the number of messages that the user has created on the platform.
 *
 * @DataProducer(
 *   id = "social_private_message_messages_sent",
 *   name = @Translation("User private messages created"),
 *   description = @Translation("The number of private messages that the user created."),
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
class PrivateMessageSent extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Resolves the request to the requested values.
   */
  public function resolve(EntityInterface $entity, RefinableCacheableDependencyInterface $metadata): int {
    // The query is copy/paste of 'user_private_message' user export plugin.
    // Get messages count for the user.
    $storage = $this->entityTypeManager->getStorage('private_message');
    if (!$storage instanceof ContentEntityStorageInterface) {
      return 0;
    }

    // Calculate the result.
    // Cast to int to satisfy the user GraphQL interface.
    // Ignore phpstan false positive accessCheck() will always evaluate to true.
    // phpcs:ignore
    /** @phpstan-ignore-next-line */
    return (int) $storage->getQuery()
      ->accessCheck()
      ->condition('owner', $entity->id())
      ->count()
      ->execute();
  }

}

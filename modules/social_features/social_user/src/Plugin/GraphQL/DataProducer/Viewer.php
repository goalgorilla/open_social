<?php

namespace Drupal\social_user\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the viewer for this request.
 *
 * This could be the authenticated user or a user that a system is acting on
 * behalf of.
 *
 * @DataProducer(
 *   id = "viewer",
 *   name = @Translation("Viewer"),
 *   description = @Translation("The actor for this request if any."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Viewer")
 *   )
 * )
 */
class Viewer extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity buffer service.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected EntityBuffer $entityBuffer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('graphql.buffer.entity')
    );
  }

  /**
   * CurrentUser constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $entityBuffer
   *   The entity buffer service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entityTypeManager,
    EntityBuffer $entityBuffer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityBuffer = $entityBuffer;
  }

  /**
   * Returns current user.
   *
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   Field context.
   *
   * @return \GraphQL\Deferred
   *   A promise that resolves to the current user.
   */
  public function resolve(FieldContext $context): Deferred {
    // Response must be cached based on current user as a cache context,
    // otherwise a new user would became a previous user.
    $context->addCacheableDependency($this->currentUser);

    $resolver = $this->entityBuffer->add('user', $this->currentUser->id());

    return new Deferred(function () use ($resolver, $context) {
      if (!$entity = $resolver()) {
        // If there is no entity with this id, add the list cache tags so that
        // the cache entry is purged whenever a new entity of this type is
        // saved.
        $type = $this->entityTypeManager->getDefinition('user');
        /** @var \Drupal\Core\Entity\EntityTypeInterface $type */
        $tags = $type->getListCacheTags();
        $context->addCacheTags($tags);
        return NULL;
      }

      $context->addCacheableDependency($entity);
      return $entity;
    });
  }

}

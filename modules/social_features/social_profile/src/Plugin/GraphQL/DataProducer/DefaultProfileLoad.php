<?php

namespace Drupal\social_profile\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads the default profile for a user.
 *
 * On many Open Social platforms there is only one profile.
 *
 * @DataProducer(
 *   id = "default_profile_load",
 *   name = @Translation("Load default profile"),
 *   description = @Translation("Loads the default profile for a user."),
 *   produces = @ContextDefinition("entity:profile",
 *     label = @Translation("Profile")
 *   ),
 *   consumes = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     )
 *   }
 * )
 */
class DefaultProfileLoad extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity buffer service.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected $entityBuffer;

  /**
   * The Drupal renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('graphql.buffer.entity'),
      $container->get('renderer')
    );
  }

  /**
   * EntityLoad constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $entityBuffer
   *   The entity buffer service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    EntityBuffer $entityBuffer,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityBuffer = $entityBuffer;
    $this->renderer = $renderer;
  }

  /**
   * Resolve to a value.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to fetch the profile for.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata.
   *
   * @return \GraphQL\Deferred|null
   *   A promise that resolves to the loaded profile or null if the user does
   *   not have a published profile.
   */
  public function resolve(AccountInterface $account, RefinableCacheableDependencyInterface $metadata) {
    $storage = $this->entityTypeManager->getStorage('profile');
    $type = $storage->getEntityType();
    $query = $storage->getQuery()
      ->currentRevision()
      ->accessCheck()
      ->condition('uid', $account->id())
      ->condition('type', 'profile')
      ->condition('status', TRUE)
      ->sort('is_default', 'DESC')
      ->sort('profile_id', 'DESC')
      ->range(0, 1);

    $metadata->addCacheTags($type->getListCacheTags());
    $metadata->addCacheContexts($type->getListCacheContexts());

    // Queries using the Entity Access API may have cacheability information
    // that must be captured until we have a better way from Drupal core
    // see https://www.drupal.org/project/drupal/issues/3028976.
    $query_context = new RenderContext();
    $result = $this->renderer->executeInRenderContext(
      $query_context,
      fn () => $query->execute()
    );

    if (!$query_context->isEmpty()) {
      $metadata->addCacheableDependency($query_context->pop());
    }

    if (empty($result)) {
      return NULL;
    }

    $resolver = $this->entityBuffer->add('profile', reset($result));

    return new Deferred(function () use ($resolver, $metadata) {
      $entity = $resolver();
      $metadata->addCacheableDependency($entity);
      return $entity;
    });
  }

}

<?php

declare(strict_types=1);

namespace Drupal\social_profile\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads a single group membership.
 */
#[DataProducer(
  id: "group_membership",
  name: new TranslatableMarkup("Load group membership"),
  description: new TranslatableMarkup("Loads a single group membership entity."),
  produces: new ContextDefinition(
    data_type: "entity",
    label: new TranslatableMarkup("Entity")
  ),
  consumes: [
    "group_id" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Group ID")
    ),
    "user_id" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("User ID"),
      required: FALSE
    ),
    "access" => new ContextDefinition(
      data_type: "boolean",
      label: new TranslatableMarkup("Check access"),
      required: FALSE,
      default_value: FALSE
    ),
    "access_user" => new EntityContextDefinition(
      data_type: "entity:user",
      label: new TranslatableMarkup("User"),
      required: FALSE,
      default_value: NULL
    ),
    "access_operation" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Operation"),
      required: FALSE,
      default_value: "view"
    ),
  ]
)]
class GroupMembership extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
      $container->get('entity.repository'),
      $container->get('graphql.buffer.entity'),
      $container->get('renderer')
    );
  }

  /**
   * GroupMembership constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param array $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $entityBuffer
   *   The entity buffer service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    array $pluginDefinition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
    protected EntityBuffer $entityBuffer,
    protected RendererInterface $renderer,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * Resolver.
   *
   * @return \GraphQL\Deferred
   *   A promise that resolves to the group membership entity or NULL.
   *
   * @phpstan-return \GraphQL\Deferred<\Drupal\Core\Entity\EntityInterface|null>
   */
  public function resolve(string $group_id, string $user_id, ?bool $access, ?AccountInterface $accessUser, ?string $accessOperation, FieldContext $context): Deferred {
    $storage = $this->entityTypeManager->getStorage('group_content');
    $type = $storage->getEntityType();
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group_id)
      ->condition('entity_id', $user_id)
      ->condition('plugin_id', 'group_membership')
      ->range(0, 1);

    // Queries using the Entity Access API may have cacheability information
    // that must be captured until we have a better way from Drupal core
    // see https://www.drupal.org/project/drupal/issues/3028976.
    $query_context = new RenderContext();
    $result = $this->renderer->executeInRenderContext(
      $query_context,
      fn () => $query->execute()
    );

    if (!$query_context->isEmpty()) {
      $context->addCacheableDependency($query_context->pop());
    }

    if (empty($result)) {
      return new Deferred(fn () => NULL);
    }

    $resolver = $this->entityBuffer->add($type->id(), reset($result));

    return new Deferred(function () use ($type, $resolver, $context, $access, $accessUser, $accessOperation) {
      if (!$entity = $resolver()) {
        // If there is no entity with this id, add the list cache tags so that
        // the cache entry is purged whenever a new entity of this type is
        // saved.
        $context->addCacheTags($type->getListCacheTags());
        return NULL;
      }

      $context->addCacheableDependency($entity);

      // Check if the passed user (or current user if none is passed) has access
      // to the entity, if not return NULL.
      if ($access) {
        /** @var \Drupal\Core\Access\AccessResultInterface $accessResult */
        $accessResult = $entity->access($accessOperation, $accessUser, TRUE);
        $context->addCacheableDependency($accessResult);
        if (!$accessResult->isAllowed()) {
          return NULL;
        }
      }

      return $entity;
    });
  }

}

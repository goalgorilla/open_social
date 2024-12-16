<?php

namespace Drupal\social_comment\Plugin\GraphQL\DataProducer;

use Drupal\social_graphql\GraphQL\ConnectionInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer;
use Drupal\social_graphql\GraphQL\EntityConnection;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;
use Drupal\social_comment\Plugin\GraphQL\QueryHelper\CommentAttachmentsQueryHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queries the files on the platform.
 *
 * @DataProducer(
 *   id = "social_comment_attachments",
 *   name = @Translation("Social Files"),
 *   description = @Translation("Loads the files."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("EntityConnection")
 *   ),
 *   consumes = {
 *     "parent" = @ContextDefinition("entity",
 *       label = @Translation("Parent"),
 *       required = TRUE
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
 *       default_value = "CREATED_AT"
 *     ),
 *   }
 * )
 */
class SocialCommentAttachments extends EntityDataProducerPluginBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The factory method to create an instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface.
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param string $plugin_definition
   *   The plugin definition.
   *
   * @return self
   *   Returns the instance of this class.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('graphql.buffer.entity'),
      $container->get('graphql.buffer.entity_uuid'),
      $container->get('graphql.buffer.entity_revision'),
    );
  }

  /**
   * SocialFiles constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphqlEntityBuffer
   *   The entity buffer service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer $graphqlEntityUuidBuffer
   *   The entity uuid buffer service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer $graphqlEntityRevisionBuffer
   *   The entity revision buffer service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    EntityBuffer $graphqlEntityBuffer,
    EntityUuidBuffer $graphqlEntityUuidBuffer,
    EntityRevisionBuffer $graphqlEntityRevisionBuffer,
  ) {
    parent::__construct(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $entityTypeManager,
      $graphqlEntityBuffer,
      $graphqlEntityUuidBuffer,
      $graphqlEntityRevisionBuffer
    );
    $this->database = $database;
  }

  /**
   * Resolves the request to the requested values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   The conversation to fetch participants for.
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
   * @return \Drupal\social_graphql\GraphQL\EntityConnection|ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(EntityInterface $parent, ?int $first, ?string $after, ?int $last, ?string $before, bool $reverse, string $sortKey, RefinableCacheableDependencyInterface $metadata): EntityConnection|ConnectionInterface {
    $query_helper = new CommentAttachmentsQueryHelper($sortKey, $this->entityTypeManager, $this->graphqlEntityBuffer, $this->database, $parent);
    $metadata->addCacheableDependency($query_helper);

    $connection = new EntityConnection($query_helper);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    return $connection;
  }

}

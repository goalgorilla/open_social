<?php

namespace Drupal\social_comment\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer;
use Drupal\social_graphql\GraphQL\ConnectionInterface;
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
  protected $database;

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
      $container->get('graphql.buffer.entity_uuid'),
      $container->get('graphql.buffer.entity_revision'),
      $container->get('renderer'),
      $container->get('database')
    );
  }

  /**
   * {@inheritDoc}
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
          $pluginId,
    array $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    EntityBuffer $graphqlEntityBuffer,
    EntityUuidBuffer $graphqlEntityUuidBuffer,
    EntityRevisionBuffer $graphqlEntityRevisionBuffer,
    RendererInterface $renderer,
    Connection $database
  ) {
    parent::__construct(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $entityTypeManager,
      $graphqlEntityBuffer,
      $graphqlEntityUuidBuffer,
      $graphqlEntityRevisionBuffer,
      $renderer
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
   * @return \Drupal\social_graphql\GraphQL\ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(EntityInterface $parent, ?int $first, ?string $after, ?int $last, ?string $before, bool $reverse, string $sortKey, RefinableCacheableDependencyInterface $metadata): ConnectionInterface {
    $query_helper = new CommentAttachmentsQueryHelper($sortKey, $this->entityTypeManager, $this->graphqlEntityBuffer, $this->renderer, $this->database, $parent);
    $metadata->addCacheableDependency($query_helper);

    $connection = new EntityConnection($query_helper, $this->renderer);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    return $connection;
  }

}

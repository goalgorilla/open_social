<?php

namespace Drupal\social_user\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer;
use Drupal\social_graphql\GraphQL\EmptyEntityConnection;
use Drupal\social_graphql\GraphQL\EntityConnection;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;
use Drupal\social_user\GraphQL\QueryHelper\UserQueryHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queries the users on the platform.
 *
 * @DataProducer(
 *   id = "query_user",
 *   name = @Translation("Query a list of users"),
 *   description = @Translation("Retrieves a list of teas."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("User connection")
 *   ),
 *   consumes = {
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
class QueryUser extends EntityDataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

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
      $container->get('current_user')
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
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphqlEntityBuffer
   *   The GraphQL entity buffer.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer $graphqlEntityUuidBuffer
   *   The GraphQL entity uuid buffer.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer $graphqlEntityRevisionBuffer
   *   The GraphQL entity revision buffer.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
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
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $entityTypeManager, $graphqlEntityBuffer, $graphqlEntityUuidBuffer, $graphqlEntityRevisionBuffer);
    $this->currentUser = $current_user;
  }

  /**
   * Resolves the request to the requested values.
   *
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
   *
   * @todo https://www.drupal.org/project/social/issues/3191622
   * @todo https://www.drupal.org/project/social/issues/3191637
   */
  public function resolve(?int $first, ?string $after, ?int $last, ?string $before, bool $reverse, string $sortKey, RefinableCacheableDependencyInterface $metadata) {
    // This is a quick fix due to an incorrect entity access layer for users
    // do not copy this, but fix your access checks instead.
    if (!$this->currentUser->hasPermission('list user')) {
      return new EmptyEntityConnection();
    }

    $query_helper = new UserQueryHelper($sortKey, $this->entityTypeManager, $this->graphqlEntityBuffer);
    $metadata->addCacheableDependency($query_helper);

    $connection = new EntityConnection($query_helper);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    return $connection;
  }

}

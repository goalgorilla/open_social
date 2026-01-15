<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\GraphQL\EntityConnection;
use Drupal\social_tagging\Plugin\GraphQL\QueryHelper\ContentTagQueryHelper;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;

/**
 * Returns all tags in a category with pagination support.
 *
 * @DataProducer(
 *   id = "tags_in_category",
 *   name = @Translation("Tags In Category"),
 *   description = @Translation("Returns a paginated connection of all tags (child taxonomy terms) that belong to the specified tag category. Supports cursor-based pagination (first, last, after, before) and sorting by weight. Only returns published tags."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("EntityConnection")
 *   ),
 *   consumes = {
 *     "category" = @ContextDefinition("entity:taxonomy_term",
 *       label = @Translation("Category"),
 *       required = TRUE
 *     ),
 *     "after" = @ContextDefinition("string",
 *       label = @Translation("After"),
 *       required = FALSE
 *     ),
 *     "before" = @ContextDefinition("string",
 *       label = @Translation("Before"),
 *       required = FALSE
 *     ),
 *     "first" = @ContextDefinition("integer",
 *       label = @Translation("First"),
 *       required = FALSE
 *     ),
 *     "last" = @ContextDefinition("integer",
 *       label = @Translation("Last"),
 *       required = FALSE
 *     ),
 *     "reverse" = @ContextDefinition("boolean",
 *       label = @Translation("Reverse"),
 *       required = FALSE,
 *       default_value = FALSE
 *     ),
 *   }
 * )
 */
class TagsInCategory extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The GraphQL entity buffer.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected EntityBuffer $graphqlEntityBuffer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->graphqlEntityBuffer = $container->get('graphql.buffer.entity');
    return $instance;
  }

  /**
   * Resolves the request to the requested values.
   *
   * @param \Drupal\taxonomy\TermInterface $category
   *   The category term.
   * @param string|null $after
   *   Cursor to fetch results after.
   * @param string|null $before
   *   Cursor to fetch results before.
   * @param int|null $first
   *   Fetch the first X results.
   * @param int|null $last
   *   Fetch the last X results.
   * @param bool $reverse
   *   Reverses the order of the data.
   *
   * @return \Drupal\social_graphql\GraphQL\EntityConnection
   *   The entity connection with pagination.
   */
  public function resolve(TermInterface $category, ?string $after, ?string $before, ?int $first, ?int $last, bool $reverse): EntityConnection {
    $query_helper = new ContentTagQueryHelper(
      (int) $category->id(),
      'WEIGHT',
      $this->entityTypeManager,
      $this->graphqlEntityBuffer
    );

    $connection = new EntityConnection($query_helper);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    return $connection;
  }

}

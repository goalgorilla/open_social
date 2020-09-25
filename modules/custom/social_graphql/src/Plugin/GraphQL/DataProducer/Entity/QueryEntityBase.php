<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\EdgeInterface;
use Drupal\social_graphql\Wrappers\EntityEdge;
use Drupal\social_graphql\Wrappers\EntityConnection;
use GraphQL\Deferred;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for data producers that produce paginated entity links.
 *
 * This class implements helper methods to easily add pagination based on the
 * Relay Connection specification.
 *
 * This class does not support revision queries.
 */
abstract class QueryEntityBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The class to use for the connection.
   *
   * @var string
   */
  protected static $connectionClass = EntityConnection::class;

  /**
   * The class to use as edges.
   *
   * @var string
   */
  protected static $edgeClass = EntityEdge::class;

  /**
   * The highest limit a client is allowed to specify.
   */
  protected const MAX_LIMIT = 100;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
      $container->get('entity_type.manager')
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
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Turns a query into a GraphQL conenction.
   *
   * Takes a query with sorting and pagination argument and applies the filters
   * for pagination. Then executes the query and returns a connection containing
   * the result and page info.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query to use for data fetching.
   * @param int|null $first
   *   The limit of N first results (either first XOR last must be set).
   * @param string|null $after
   *   The cursor after which to fetch results (when using `$first`).
   * @param int|null $last
   *   The limit of N last results (either first XOR last must be set).
   * @param string|null $before
   *   The cursor before which to fetch results (when using `$last`).
   * @param bool $reverse
   *   Whether the sorting is in reversed order.
   * @param string $sortKey
   *   The key to sort by (resolved to a field with ::getSortField).
   *
   * @return \Drupal\social_graphql\Wrappers\ConnectionInterface
   *   The connection that provides information about the fetched entities.
   */
  protected function resolvePaginatedQuery(QueryInterface $query, ?int $first, ?string $after, ?int $last, ?string $before, bool $reverse, string $sortKey) {
    // Apply pagination to the query. The return value tells us if the order is
    // reversed or not which can help us determine if the connection needs to
    // reverse the result to get the user requested order.
    $is_reversed = $this->applyPagination($query, $first, $after, $last, $before, $reverse, $sortKey);

    // Fetch the result of the query.
    $result = $query->execute();

    // In case of no results we create a promise that resolves to an empty
    // array. This allows callers of this function to always consume promises.
    if (empty($result)) {
      $promise = new Deferred(function () {
        return [];
      });
    }
    // Otherwise we create a callback that uses the GraphQL entity buffer to
    // ensure the entities for this query are only loaded once. Even if the
    // results are used multiple times.
    else {
      $buffer = \Drupal::service('graphql.buffer.entity');
      $callback = $buffer->add($query->getEntityTypeId(), array_values($result));
      $promise = new Deferred(function () use ($callback) {
        // Fetch the results from the buffer and convert them to edges.
        return array_map([$this, "entityToEdge"], $callback());
      });
    }

    return new static::$connectionClass($promise, $first, $after, $last, $before, $is_reversed);
  }

  /**
   * Applies pagination to a provided entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query being created.
   * @param int|null $first
   *   Whether to select the first N results.
   * @param string|null $after
   *   The cursor to select elements after.
   * @param int|null $last
   *   Whether to select the last N results.
   * @param string|null $before
   *   The cursor to select elements before.
   * @param bool|null $reverse
   *   Whether sorting should happen in reverse order before filtering.
   * @param string $sortKey
   *   The field used for sorting.
   *
   * @return bool
   *   Whether pagination has caused the results to be reversed.
   */
  private function applyPagination(QueryInterface $query, ?int $first, ?string $after, ?int $last, ?string $before,  bool $reverse, string $sortKey) : bool {
    $this->assertValidPagination($first, $after, $last, $before, self::MAX_LIMIT);

    $sort_field = $this->getSortField($sortKey);
    $id_field = $this->entityTypeManager->getDefinition($query->getEntityTypeId())->getKey('id');

    // Because MySQL only allows us to provide positive range limits (we can't
    // select backwards) we must change the query order based on the meaning of
    // first and last. This in turn is dependant on whether we're selecting in
    // ascending (non-reversed) or descending (reversed) order.
    // The order is ascending if
    // - we want the first results in a non reversed query
    // - we want the last results in a reversed query
    // The order is descending if
    // - we want the first results in a reversed query
    // - we want the last results in a non reversed query.
    $query_order = (!is_null($first) && !$reverse) || (!is_null($last) && $reverse) ? 'ASC' : 'DESC';

    // If a cursor is provided then we alter the condition to select the
    // elements on the correct side of the cursor.
    $cursor = $after ?? $before;
    if (!is_null($cursor)) {
      /** @var \Drupal\user\UserInterface|null $cursor_entity */
      $cursor_entity = EntityEdge::nodeFromCursor($cursor, $query->getEntityTypeId());
      if (is_null($cursor_entity)) {
        throw new UserError("invalid cursor '${$cursor}'");
      }
      $pagination_condition = $query->orConditionGroup();

      $operator = (!is_null($before) && !$reverse) || (!is_null($after) && $reverse) ? '<' : '>';
      $cursor_value = $this->getEntityValue($cursor_entity, $sortKey);
      $pagination_condition->condition($sort_field, $cursor_value, $operator);
      // If the sort field is different than the ID then it's not guaranteed to
      // be unique. However, above we exclude values that are the same as those
      // of the cursor. We want to include those but use the ID to make sure
      // they're on the correct side of the cursor.
      if ($sort_field !== $id_field) {
        $pagination_condition->condition(
          $query->andConditionGroup()
            ->condition($sort_field, $cursor_value, '=')
            ->condition($id_field, $cursor_entity->id(), $operator)
        );
      }

      $query->condition($pagination_condition);
    }

    // From assertValidPagination we know that we either have a first or a last.
    $limit = $first ?? $last;

    // Fetch N + 1 so we know if there are more pages.
    $query->range(0, $limit + 1);
    $query->sort(
      $sort_field,
      $query_order
    );

    // To ensure a consistent sorting for duplicate fields we add a secondary
    // sort based on the ID.
    if ($sort_field !== $id_field) {
      $query->sort(
        $id_field,
        $query_order
      );
    }

    // To compensate for the ordering needed for the range selector we must
    // sometimes flip the result. The first 3 results of a non-reverse query are
    // the same as the last 3 results of a reversed query but they are in
    // reverse order.
    // The results must be flipped if
    // - we want the last results in a reversed query
    // - we want the last results in a non reversed query.
    return !is_null($last);
  }

  /**
   * Ensures the user entered limits (first/last) are valid.
   *
   * @param int|null $first
   *   Request to retrieve first n results.
   * @param string|null $after
   *   The cursor after which to fetch results.
   * @param int|null $last
   *   Request to retrieve last n results.
   * @param string|null $before
   *   The cursor before which to fetch results.
   * @param int $limit
   *   The limit on the amount of results that may be requested.
   *
   * @throws \GraphQL\Error\UserError
   *   Error thrown when a user has specified invalid arguments.
   */
  private function assertValidPagination(?int $first, ?string $after, ?int $last, ?string $before, int $limit) : void {
    // The below if-statements are derived to be able to implement the Relay
    // connection spec in a sane way. They ensure we only ever need to care
    // about either (first and after) or (last and before) and no other
    // combinations.
    if (is_null($first) && is_null($last)) {
      throw new UserError("You must provide one of first or last");
    }
    if (!is_null($first) && !is_null($last)) {
      throw new UserError("providing both first and last is not supported");
    }
    if (!is_null($first) && !is_null($before)) {
      throw new UserError("using first with before is not supported");
    }
    if (!is_null($last) && !is_null($after)) {
      throw new UserError("using last with after is not supported");
    }
    if ($first <= 0 && !is_null($first)) {
      throw new UserError("first must be a positive integer when provided");
    }
    if ($last <= 0 && !is_null($last)) {
      throw new UserError("last must be a positive integer when provided");
    }
    if ($first > $limit) {
      throw new UserError("first may not be larger than " . $limit);
    }
    if ($last > $limit) {
      throw new UserError("first may not be larger than " . $limit);
    }
  }

  /**
   * Wraps a loaded entity into an edge wrapper.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   *
   * @return \Drupal\social_graphql\Wrappers\EdgeInterface
   *   An entity edge that knows how to create a cursor.
   */
  protected function entityToEdge(EntityInterface $entity) : EdgeInterface {
    return new static::$edgeClass($entity);
  }

  /**
   * Translate a user facing sort key to an entity query field.
   *
   * @param string $sortKey
   *   The user facing sort key.
   *
   * @return string
   *   The database sort key.
   */
  abstract protected function getSortField(string $sortKey) : string;

  /**
   * Get the value for an entity based on the sort key.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The cursor entity to get a value from.
   * @param string $sortKey
   *   The user facing sort key.
   */
  abstract protected function getEntityValue(EntityInterface $entity, string $sortKey);

}

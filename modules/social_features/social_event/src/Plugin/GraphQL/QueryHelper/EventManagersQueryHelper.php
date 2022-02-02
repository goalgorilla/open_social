<?php

namespace Drupal\social_event\Plugin\GraphQL\QueryHelper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\ConnectionQueryHelperBase;
use Drupal\social_graphql\Wrappers\Cursor;
use Drupal\social_graphql\Wrappers\Edge;
use Drupal\user\UserInterface;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Loads event managers.
 */
class EventManagersQueryHelper extends ConnectionQueryHelperBase {

  /**
   * The event for which managers are being fetched.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $event;

  /**
   * EventManagersQueryHelper constructor.
   *
   * @param string $sort_key
   *   The key that is used for sorting.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphql_entity_buffer
   *   The GraphQL entity buffer.
   * @param \Drupal\node\NodeInterface $event
   *   The event.
   */
  public function __construct(
    string $sort_key,
    EntityTypeManagerInterface $entity_type_manager,
    EntityBuffer $graphql_entity_buffer,
    NodeInterface $event
  ) {
    parent::__construct($sort_key, $entity_type_manager, $graphql_entity_buffer);

    $this->event = $event;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() : QueryInterface {
    // This slightly inefficiently loads all referenced entities. Unfortunately
    // we want to load user entities and Drupal core does not support joining
    // other entity types in entity queries. The below can be optimised with a
    // more efficient direct database query to load the event manager user ids
    // but this requires properly getting all the table names (taking database
    // prefixes into account). That's more effort than it's worth for a field
    // that realistically will not have more than a handful values usually.
    // The upside is that any users we match in the eventual query will probably
    // need to be loaded since a GraphQL query is likely to want more data for a
    // user. So optimisation may not win you a lot.
    // The filter is added since entity reference fields may be configured to
    // reference other values than users, but this is something we don't (yet)
    // support.
    $users = $this->event->field_event_managers->referencedEntities();
    $uids = array_map(
      fn (UserInterface $user) => $user->id(),
      array_filter(
        $users,
        fn (EntityInterface $entity) => $entity instanceof UserInterface
      )
    );

    return $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $uids ?: NULL, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function getCursorObject(string $cursor) : ?Cursor {
    $cursor_object = Cursor::fromCursorString($cursor);

    return !is_null($cursor_object) && $cursor_object->isValidFor($this->sortKey, 'node')
      ? $cursor_object
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdField() : string {
    return 'uid';
  }

  /**
   * {@inheritdoc}
   */
  public function getSortField() : string {
    switch ($this->sortKey) {
      case 'CREATED_AT':
        return 'created';

      default:
        throw new \InvalidArgumentException("Unsupported sortKey for sorting '{$this->sortKey}'");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregateSortFunction() : ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoaderPromise(array $result) : SyncPromise {
    // In case of no results we create a callback the returns an empty array.
    if (empty($result)) {
      $callback = static fn () => [];
    }
    // Otherwise we create a callback that uses the GraphQL entity buffer to
    // ensure the entities for this query are only loaded once. Even if the
    // results are used multiple times.
    else {
      $buffer = \Drupal::service('graphql.buffer.entity');
      $callback = $buffer->add('user', array_values($result));
    }

    return new Deferred(
      function () use ($callback) {
        return array_map(
          fn (UserInterface $entity) => new Edge(
            $entity,
            new Cursor('user', $entity->id(), $this->sortKey, $this->getSortValue($entity))
          ),
          $callback()
        );
      }
    );
  }

  /**
   * Get the value for an entity based on the sort key for this connection.
   *
   * @param \Drupal\user\UserInterface $user
   *   The moderator entity for the user in this conversation.
   *
   * @return mixed
   *   The sort value.
   */
  protected function getSortValue(UserInterface $user) {
    switch ($this->sortKey) {
      case 'CREATED_AT':
        return $user->getCreatedTime();

      default:
        throw new \InvalidArgumentException("Unsupported sortKey for pagination '{$this->sortKey}'");
    }
  }

}

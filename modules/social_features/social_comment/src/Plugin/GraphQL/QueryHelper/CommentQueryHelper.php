<?php

namespace Drupal\social_comment\Plugin\GraphQL\QueryHelper;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\ConnectionQueryHelperBase;
use Drupal\social_graphql\Wrappers\Cursor;
use Drupal\social_graphql\Wrappers\Edge;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Loads comments.
 */
class CommentQueryHelper extends ConnectionQueryHelperBase {

  /**
   * The node for which comments are being fetched.
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $parent;

  /**
   * CommentQueryHelper constructor.
   *
   * @param string $sort_key
   *   The key that is used for sorting.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphql_entity_buffer
   *   The GraphQL entity buffer.
   * @param \Drupal\node\NodeInterface|null $parent
   *   The node for which comments are being fetched.
   */
  public function __construct(string $sort_key, EntityTypeManagerInterface $entity_type_manager, EntityBuffer $graphql_entity_buffer, ?NodeInterface $parent) {
    parent::__construct($sort_key, $entity_type_manager, $graphql_entity_buffer);
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() : QueryInterface {
    $query = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->currentRevision()
      ->accessCheck(TRUE);

    if ($this->parent instanceof NodeInterface) {
      $query->condition('entity_id', $this->parent->id());
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getCursorObject(string $cursor) : ?Cursor {
    $cursor_object = Cursor::fromCursorString($cursor);

    return !is_null($cursor_object) && $cursor_object->isValidFor($this->sortKey, 'comment')
      ? $cursor_object
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdField() : string {
    return 'cid';
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
      $callback = $buffer->add('comment', array_values($result));
    }

    return new Deferred(
      function () use ($callback) {
        return array_map(
          fn (Comment $entity) => new Edge(
            $entity,
            new Cursor('comment', $entity->id(), $this->sortKey, $this->getSortValue($entity))
          ),
          $callback()
        );
      }
    );
  }

  /**
   * Get the value for an entity based on the sort key for this connection.
   *
   * @param \Drupal\comment\Entity\Comment $comment
   *   The comment entity.
   *
   * @return mixed
   *   The sort value.
   */
  protected function getSortValue(Comment $comment) {
    switch ($this->sortKey) {
      case 'CREATED_AT':
        return $comment->getCreatedTime();

      default:
        throw new \InvalidArgumentException("Unsupported sortKey for pagination '{$this->sortKey}'");
    }
  }

}

<?php

namespace Drupal\social_user\GraphQL\QueryHelper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\social_graphql\GraphQL\ConnectionQueryHelperInterface;
use Drupal\social_graphql\Wrappers\EntityEdge;
use Drupal\user\Entity\User;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Loads users.
 */
class UserQueryHelper implements ConnectionQueryHelperInterface {

  /**
   * The Drupal entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The key that is used for sorting.
   */
  protected string $sortKey;

  /**
   * UserQueryHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param string $sort_key
   *   The key that is used for sorting.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, string $sort_key) {
    $this->entityTypeManager = $entity_type_manager;
    $this->sortKey = $sort_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() : QueryInterface {
    return $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->currentRevision()
      ->accessCheck()
      // Exclude the anonymous user from listings because it doesn't make sense
      // in overview pages.
      ->condition('uid', 0, '!=');
  }

  /**
   * {@inheritdoc}
   */
  public function getCursorObject(string $cursor) {
    // TODO: This doesn't work.
    return EntityEdge::nodeFromCursor($cursor, 'user');
  }

  /**
   * {@inheritdoc}
   */
  public function getCursorValue($entity) {
    switch ($this->sortKey) {
      //      case 'FIRST_NAME':
      //        // TODO: Profile data not available for users.
      //        break;
      //      case 'LAST_NAME':
      //        // TODO: Profile data not available for users.
      //        break;
      case 'CREATED_AT':
        return $entity->getCreatedTime();

      default:
        throw new \InvalidArgumentException("Unsupported sortKey for pagination '{$this->sortKey}'");
    }
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
      case 'FIRST_NAME':
        return 'field_first_name';

      case 'LAST_NAME':
        return 'field_last_name';

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
          static fn (User $entity) => new EntityEdge($entity),
          $callback()
        );
      }
    );
  }

}

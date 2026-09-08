<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Plugin\GraphQL\QueryHelper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\social_graphql\GraphQL\ConnectionQueryHelperBase;
use Drupal\social_graphql\Wrappers\Cursor;
use Drupal\social_graphql\Wrappers\Edge;
use Drupal\user\UserInterface;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Loads a user's flexible group memberships visible to the current viewer.
 */
final class UserGroupMembershipsQueryHelper extends ConnectionQueryHelperBase {

  /**
   * The entity type.
   */
  private const string ENTITY_TYPE = 'group_content';

  /**
   * Query tag to restrict memberships to viewer-visible groups.
   *
   * @see \Drupal\social_group_flexible_group\Hooks\UserGroupMembershipsQuery
   */
  public const string VISIBLE_GROUPS_QUERY_TAG = 'user_group_memberships_visible_groups';

  /**
   * UserGroupMembershipsQueryHelper constructor.
   */
  public function __construct(
    string $sort_key,
    EntityTypeManagerInterface $entity_type_manager,
    EntityBuffer $graphql_entity_buffer,
    protected UserInterface $member,
  ) {
    parent::__construct($sort_key, $entity_type_manager, $graphql_entity_buffer);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery(): QueryInterface {
    return $this->entityTypeManager->getStorage(self::ENTITY_TYPE)
      ->getQuery()
      ->accessCheck(FALSE)
      ->addTag(self::VISIBLE_GROUPS_QUERY_TAG)
      ->condition('type', 'flexible_group-group_membership')
      ->condition('entity_id', $this->member->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getCursorObject(string $cursor): ?Cursor {
    $cursor_object = Cursor::fromCursorString($cursor);

    return $cursor_object !== NULL && $cursor_object->isValidFor($this->sortKey, self::ENTITY_TYPE)
      ? $cursor_object
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdField(): string {
    return 'id';
  }

  /**
   * {@inheritdoc}
   */
  public function getSortField(): string {
    return match ($this->sortKey) {
      'JOINED_AT' => 'created',
      default => throw new \InvalidArgumentException("Unsupported sortKey for sorting '{$this->sortKey}'"),
    };
  }

  /**
   * {@inheritdoc}
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise<array<\Drupal\social_graphql\Wrappers\Edge>, \Throwable>
   *   A promise that resolves into the edges for this connection.
   */
  public function getLoaderPromise(array $result): SyncPromise {
    if ($result === []) {
      $callback = static fn (): array => [];
    }
    else {
      $callback = $this->graphqlEntityBuffer->add(self::ENTITY_TYPE, array_values($result));
    }

    return new Deferred(
      fn(): array => array_map(
        function (GroupMembershipInterface $entity): Edge {
          $cursor = new Cursor(self::ENTITY_TYPE, (int) $entity->id(), $this->sortKey, $this->getSortValue($entity));
          return new Edge($entity, $cursor->toCursorString());
        },
        $callback()
      )
    );
  }

  /**
   * Gets the sort value for a membership.
   */
  private function getSortValue(GroupMembershipInterface $membership): int {
    return match ($this->sortKey) {
      'JOINED_AT' => (int) $membership->getCreatedTime(),
      default => throw new \InvalidArgumentException("Unsupported sortKey for pagination '{$this->sortKey}'"),
    };
  }

}

<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\QueryHelper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\social_graphql\GraphQL\ConnectionQueryHelperBase;
use Drupal\social_graphql\Wrappers\Cursor;
use Drupal\social_graphql\Wrappers\Edge;
use Drupal\taxonomy\Entity\Term;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Loads content tags (taxonomy terms) for a specific category.
 */
class ContentTagQueryHelper extends ConnectionQueryHelperBase {

  /**
   * Create a new connection query helper.
   *
   * @param int $parentId
   *   The parent category term ID.
   * @param string $sort_key
   *   The key that is used for sorting.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphql_entity_buffer
   *   The GraphQL entity buffer.
   */
  public function __construct(
    protected int $parentId,
    string $sort_key,
    EntityTypeManagerInterface $entity_type_manager,
    EntityBuffer $graphql_entity_buffer,
  ) {
    parent::__construct($sort_key, $entity_type_manager, $graphql_entity_buffer);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery(): QueryInterface {
    // Query taxonomy terms in the social_tagging vocabulary
    // that have the specified parent.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $term_storage->getQuery();
    return $query
      ->condition('vid', 'social_tagging')
      ->condition('parent', $this->parentId)
      ->accessCheck();
  }

  /**
   * {@inheritdoc}
   */
  public function getCursorObject(string $cursor): ?Cursor {
    $cursor_object = Cursor::fromCursorString($cursor);

    return !is_null($cursor_object) && $cursor_object->isValidFor($this->sortKey, 'taxonomy_term')
      ? $cursor_object
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdField(): string {
    return 'tid';
  }

  /**
   * {@inheritdoc}
   */
  public function getSortField(): string {
    return 'weight';
  }

  /**
   * {@inheritdoc}
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise<array<\Drupal\social_graphql\Wrappers\Edge>, \Throwable>
   *   A promise that resolves into the edges for this connection.
   */
  public function getLoaderPromise(array $result): SyncPromise {
    // In case of no results we create a callback the returns an empty array.
    $callback = static fn (): array => [];
    if (!empty($result)) {
      $callback = $this->graphqlEntityBuffer->add('taxonomy_term', array_values($result));
    }

    return new Deferred(
      function () use ($callback): array {
        $entities = array_filter($callback());
        return array_map(
          function (Term $term): Edge {
            $cursor = new Cursor('taxonomy_term', (int) $term->id(), $this->sortKey, $this->getSortValue($term));
            return new Edge($term, $cursor->toCursorString());
          },
          $entities
        );
      }
    );
  }

  /**
   * Get the value for an entity based on the sort key for this connection.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The taxonomy term entity.
   *
   * @return mixed
   *   The sort value.
   */
  protected function getSortValue(Term $term) {
    // Use weight for sorting, fallback to 0 if not set.
    return $term->getWeight();
  }

}

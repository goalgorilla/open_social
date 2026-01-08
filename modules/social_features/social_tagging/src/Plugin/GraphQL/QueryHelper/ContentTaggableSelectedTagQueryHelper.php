<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\QueryHelper;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\social_graphql\GraphQL\ConnectionQueryHelperBase;
use Drupal\social_graphql\Wrappers\Cursor;
use Drupal\social_graphql\Wrappers\Edge;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorageInterface;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Loads selected content tags for an entity filtered by category.
 */
class ContentTaggableSelectedTagQueryHelper extends ConnectionQueryHelperBase {

  /**
   * The entity that has the tags.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $entity;

  /**
   * The category term ID to filter tags by.
   *
   * @var int
   */
  protected int $categoryId;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected TermStorageInterface $termStorage;

  /**
   * Create a new connection query helper.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that has the tags.
   * @param int $category_id
   *   The category term ID to filter tags by.
   * @param string $sort_key
   *   The key that is used for sorting.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphql_entity_buffer
   *   The GraphQL entity buffer.
   */
  public function __construct(ContentEntityInterface $entity, int $category_id, string $sort_key, EntityTypeManagerInterface $entity_type_manager, EntityBuffer $graphql_entity_buffer) {
    $this->entity = $entity;
    $this->categoryId = $category_id;
    $this->entityTypeManager = $entity_type_manager;
    $this->graphqlEntityBuffer = $graphql_entity_buffer;
    $this->sortKey = $sort_key;
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery(): QueryInterface {
    // Get selected tag IDs from the entity's social_tagging field.
    $selected_tag_ids = [];
    if ($this->entity->hasField('social_tagging') && !$this->entity->get('social_tagging')->isEmpty()) {
      $field_values = $this->entity->get('social_tagging')->getValue();
      $selected_tag_ids = array_column($field_values, 'target_id');
    }

    // If no tags are selected, return an empty query.
    if (empty($selected_tag_ids)) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $query = $term_storage->getQuery();
      return $query
        ->condition('tid', NULL, 'IS NULL')
        ->accessCheck();
    }

    // Load all selected tags to check their parent category.
    $selected_tags = $this->termStorage->loadMultiple($selected_tag_ids);
    $filtered_tag_ids = [];

    foreach ($selected_tags as $tag) {
      if (!$tag->isPublished()) {
        continue;
      }

      // Check if tag belongs to the specified category.
      $parents = $this->termStorage->loadParents((int) $tag->id());
      if (!empty($parents)) {
        $parent = reset($parents);
        // The parent->id() returns string but categoryId is int.
        if ((int) $parent->id() === $this->categoryId) {
          // Only include tags that are children of the category,
          // not the category itself.
          $filtered_tag_ids[] = $tag->id();
        }
      }
      // Do not include the category itself when it's top-level.
      // Top-level categories should have empty contentTags.
    }

    // If no tags match the category, return an empty query.
    if (empty($filtered_tag_ids)) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $query = $term_storage->getQuery();
      return $query
        ->condition('tid', NULL, 'IS NULL')
        ->accessCheck();
    }

    // Query taxonomy terms that match the filtered IDs.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $term_storage->getQuery();
    $query = $query
      ->condition('vid', 'social_tagging')
      ->condition('tid', $filtered_tag_ids, 'IN')
      ->accessCheck();

    return $query;
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
      fn(): array => array_map(
        fn (Term $term): Edge => new Edge(
          $term,
          (string) new Cursor('taxonomy_term', (int) $term->id(), $this->sortKey, $this->getSortValue($term))
        ),
        $callback()
      )
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

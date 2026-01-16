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
    parent::__construct($sort_key, $entity_type_manager, $graphql_entity_buffer);

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
      $query = $this->termStorage->getQuery();
      return $query
        ->condition('tid', NULL, 'IS NULL')
        ->accessCheck();
    }

    // Process tags in batches to avoid memory issues.
    $filtered_tag_ids = $this->processTagsInBatches($selected_tag_ids);

    // If no tags match the category, return an empty query.
    if (empty($filtered_tag_ids)) {
      $query = $this->termStorage->getQuery();
      return $query
        ->condition('tid', NULL, 'IS NULL')
        ->accessCheck();
    }

    // Query taxonomy terms that match the filtered IDs.
    $query = $this->termStorage->getQuery();
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
   * Processes tags in batches and filters by category.
   *
   * @param array $selected_tag_ids
   *   Array of tag IDs to process.
   *
   * @return array
   *   Array of filtered tag IDs that belong to the category.
   */
  private function processTagsInBatches(array $selected_tag_ids): array {
    $batch_size = 25;
    $tag_id_chunks = array_chunk($selected_tag_ids, $batch_size);
    $filtered_tag_ids = [];

    foreach ($tag_id_chunks as $tag_id_chunk) {
      // Load tags for this batch.
      /** @var array<int, \Drupal\taxonomy\Entity\Term|null> $loaded_tags */
      $loaded_tags = $this->termStorage->loadMultiple($tag_id_chunk);
      // Filter out NULL values (tags that were deleted between query and load).
      /** @var \Drupal\taxonomy\Entity\Term[] $selected_tags */
      $selected_tags = array_values(array_filter($loaded_tags, function ($tag): bool {
        return $tag !== NULL && $tag->isPublished();
      }));

      if (empty($selected_tags)) {
        continue;
      }

      // Pre-collect parent IDs and load them in bulk to avoid N+1 queries.
      $parent_map = [];
      foreach ($selected_tags as $tag) {
        $parent_target_id = $tag->get('parent')->target_id;
        if (!empty($parent_target_id) && (int) $parent_target_id !== 0) {
          $parent_map[$tag->id()] = (int) $parent_target_id;
        }
      }

      // Load all parents in a single query.
      $parent_ids = array_unique(array_values($parent_map));
      /** @var array<int, \Drupal\taxonomy\Entity\Term> $parents */
      $parents = [];
      if (!empty($parent_ids)) {
        /** @var array<int, \Drupal\taxonomy\Entity\Term|null> $loaded_parents */
        $loaded_parents = $this->termStorage->loadMultiple($parent_ids);
        $parents = array_filter($loaded_parents, function ($parent): bool {
          return $parent !== NULL;
        });
      }

      // Filter tags by category using the pre-loaded parents.
      foreach ($selected_tags as $tag) {
        $tag_id = $tag->id();
        if (isset($parent_map[$tag_id])) {
          $parent_id = $parent_map[$tag_id];
          if (isset($parents[$parent_id]) && (int) $parents[$parent_id]->id() === $this->categoryId) {
            // Only include tags that are children of the category,
            // not the category itself.
            $filtered_tag_ids[] = $tag_id;
          }
        }
        // Do not include the category itself when it's top-level.
        // Top-level categories should have empty contentTags.
      }
    }

    return $filtered_tag_ids;
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

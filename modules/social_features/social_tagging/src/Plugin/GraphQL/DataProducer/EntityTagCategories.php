<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns tag categories that have tags assigned to an entity.
 *
 * @DataProducer(
 *   id = "entity_tag_categories",
 *   name = @Translation("Entity Tag Categories"),
 *   description = @Translation("Returns an array of wrapper objects, each containing a tag category and the entity. Groups the entity's assigned tags by their parent categories. Only includes categories that have at least one tag assigned to the entity. Each wrapper object contains both the category term and the entity for use in nested GraphQL queries."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Selected tag categories")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class EntityTagCategories extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected TermStorageInterface $termStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    $instance->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    return $instance;
  }

  /**
   * Resolves the selected tag categories for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that has the tags.
   *
   * @return array
   *   Array of objects with 'category' and 'entity' properties.
   */
  public function resolve(ContentEntityInterface $entity): array {
    // Get selected tag IDs from the entity's social_tagging field.
    $selected_tag_ids = [];
    if ($entity->hasField('social_tagging') && !$entity->get('social_tagging')->isEmpty()) {
      $field_values = $entity->get('social_tagging')->getValue();
      $selected_tag_ids = array_column($field_values, 'target_id');
    }

    // If no tags are selected, return an empty array.
    if (empty($selected_tag_ids)) {
      return [];
    }

    // Process tags in batches to avoid memory issues.
    $batch_size = 25;
    $tag_id_chunks = array_chunk($selected_tag_ids, $batch_size);
    $categories = [];

    foreach ($tag_id_chunks as $tag_id_chunk) {
      // Load tags for this batch.
      /** @var array<int, \Drupal\taxonomy\Entity\Term|null> $loaded_tags */
      $loaded_tags = $this->termStorage->loadMultiple($tag_id_chunk);
      // Filter out NULL values (tags that were deleted between query and load).
      /** @var \Drupal\taxonomy\Entity\Term[] $selected_tags */
      $selected_tags = array_values(array_filter($loaded_tags, function ($tag): bool {
        return $tag !== NULL;
      }));

      if (empty($selected_tags)) {
        continue;
      }

      $batch_categories = $this->processBatch($selected_tags, $entity);
      $categories += $batch_categories;
    }

    return array_values($categories);
  }

  /**
   * Processes a batch of tags and groups them by category.
   *
   * @param array $selected_tags
   *   Array of loaded taxonomy term objects in this batch.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that has the tags.
   *
   * @return array
   *   Array of category objects keyed by category ID.
   */
  private function processBatch(array $selected_tags, ContentEntityInterface $entity): array {
    $categories = [];

    // Pre-collect parent IDs and load them in bulk.
    $parent_map = [];
    foreach ($selected_tags as $tag) {
      if (!$tag->isPublished()) {
        continue;
      }
      $parent_target_id = $tag->get('parent')->target_id;
      if (!empty($parent_target_id) && (int) $parent_target_id !== 0) {
        $parent_map[$tag->id()] = (int) $parent_target_id;
      }
    }

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

    foreach ($selected_tags as $tag) {
      if (!$tag->isPublished()) {
        continue;
      }

      $tag_id = $tag->id();
      if (isset($parent_map[$tag_id])) {
        $parent_id = $parent_map[$tag_id];
        if (isset($parents[$parent_id])) {
          $parent = $parents[$parent_id];
          if ($parent->isPublished()) {
            $category_id = $parent->id();
            if (!isset($categories[$category_id])) {
              $categories[$category_id] = (object) [
                'category' => $parent,
                'entity' => $entity,
              ];
            }
          }
        }
      }
      // If tag has no parent, it might be a category itself.
      // Check if it's a top-level term (parent = 0).
      elseif ($tag->get('parent')->isEmpty() || (int) $tag->get('parent')->target_id === 0) {
        $category_id = $tag_id;
        if (!isset($categories[$category_id])) {
          $categories[$category_id] = (object) [
            'category' => $tag,
            'entity' => $entity,
          ];
        }
      }
    }

    return $categories;
  }

}

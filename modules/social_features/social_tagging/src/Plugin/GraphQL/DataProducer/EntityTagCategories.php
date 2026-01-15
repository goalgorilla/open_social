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

    // Load all selected tags.
    $selected_tags = $this->termStorage->loadMultiple($selected_tag_ids);
    $categories = [];

    foreach ($selected_tags as $tag) {
      if (!$tag->isPublished()) {
        continue;
      }

      // Get the parent category of this tag.
      $parents = $this->termStorage->loadParents((int) $tag->id());
      if (!empty($parents)) {
        $parent = reset($parents);
        // Only include published categories.
        if ($parent->isPublished()) {
          $category_id = $parent->id();
          // Group by category - only create one entry per category.
          if (!isset($categories[$category_id])) {
            $categories[$category_id] = (object) [
              'category' => $parent,
              'entity' => $entity,
            ];
          }
        }
      }
      // If tag has no parent, it might be a category itself.
      // Check if it's a top-level term (parent = 0).
      elseif ($tag->get('parent')->isEmpty() || $tag->get('parent')->target_id == 0) {
        $category_id = $tag->id();
        // Group by category - only create one entry per category.
        if (!isset($categories[$category_id])) {
          $categories[$category_id] = (object) [
            'category' => $tag,
            'entity' => $entity,
          ];
        }
      }
    }

    return array_values($categories);
  }

}

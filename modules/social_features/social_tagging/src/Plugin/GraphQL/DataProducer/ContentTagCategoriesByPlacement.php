<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queries content tag categories filtered by placement.
 *
 * @DataProducer(
 *   id = "content_tag_categories_by_placement",
 *   name = @Translation("Content Tag Categories By Placement"),
 *   description = @Translation("Loads content tag categories (parent terms) filtered by placement."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Categories")
 *   ),
 *   consumes = {
 *     "placement" = @ContextDefinition("string",
 *       label = @Translation("Placement"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class ContentTagCategoriesByPlacement extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Resolves the request to the requested values.
   *
   * @param string $placement
   *   The placement value (e.g., 'TOPIC', 'EVENT').
   *
   * @return array
   *   Array of ContentTagCategory terms filtered by placement.
   */
  public function resolve(string $placement): array {
    // Map ENUM values (TOPIC, EVENT) to database values.
    $placement_mapping = [
      'TOPIC' => 'node_topic',
      'EVENT' => 'node_event',
    ];

    // Get the database value for this placement.
    $placement_db_value = $placement_mapping[$placement] ?? strtolower($placement);

    // Query all parent terms (parent = 0) in the social_tagging vocabulary.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $term_storage->getQuery()
      ->condition('vid', 'social_tagging')
      ->condition('parent', 0)
      ->condition('status', 1)
      ->accessCheck();

    $term_ids = $query->execute();

    if (empty($term_ids)) {
      return [];
    }

    // Load all terms and filter by placement.
    $terms = $term_storage->loadMultiple($term_ids);
    $filtered_categories = [];

    foreach ($terms as $term) {
      if (!$term->isPublished()) {
        continue;
      }

      // Check if term has the field_category_usage field.
      // Following the same pattern as ContentTagCategoryPlacement::resolve().
      if (!$term->hasField('field_category_usage')) {
        continue;
      }

      $field = $term->get('field_category_usage');
      if ($field->isEmpty()) {
        continue;
      }

      // Get the serialized placement values from the field.
      // The field is a string_long field containing a serialized array.
      $serialized_value = $field->value;
      if (empty($serialized_value)) {
        continue;
      }

      // Unserialize the value.
      $usage_values = unserialize($serialized_value);
      if (!is_array($usage_values) || empty($usage_values)) {
        continue;
      }

      // Check if the placement matches.
      // The values in the array are stored as 'node_topic', 'node_event', etc.
      $matches = FALSE;
      foreach ($usage_values as $value) {
        if (!empty($value) && is_string($value)) {
          // Compare the database value directly.
          $value_trimmed = trim($value);
          if ($value_trimmed === $placement_db_value) {
            $matches = TRUE;
            break;
          }
        }
      }

      if ($matches) {
        $filtered_categories[] = $term;
      }
    }

    return $filtered_categories;
  }

}

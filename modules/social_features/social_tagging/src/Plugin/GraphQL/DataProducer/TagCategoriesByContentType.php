<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns tag categories that can be used for a specific content type.
 *
 * @DataProducer(
 *   id = "tag_categories_by_content_type",
 *   name = @Translation("Tag Categories By Content Type"),
 *   description = @Translation("Returns all tag categories (parent taxonomy terms) that are configured to be used with a specific content type (e.g., TOPIC, EVENT). Filters categories based on their field_category_usage configuration, which determines which content types can use tags from that category."),
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
class TagCategoriesByContentType extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
    if (!isset($placement_mapping[$placement])) {
      return [];
    }
    $placement_db_value = $placement_mapping[$placement];

    // Query all parent terms (parent = 0) in the social_tagging vocabulary.
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
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

    // Process terms in batches to avoid memory issues.
    return $this->processTermsInBatches($term_storage, $term_ids, $placement_db_value);
  }

  /**
   * Processes taxonomy terms in batches and filters by placement.
   *
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   The taxonomy term storage.
   * @param array $term_ids
   *   Array of term IDs to process.
   * @param string $placement_db_value
   *   The database value for the placement (e.g., 'node_topic', 'node_event').
   *
   * @return array
   *   Array of filtered taxonomy terms that match the placement.
   */
  private function processTermsInBatches(TermStorageInterface $term_storage, array $term_ids, string $placement_db_value): array {
    $batch_size = 25;
    $term_id_chunks = array_chunk($term_ids, $batch_size);
    $filtered_categories = [];

    foreach ($term_id_chunks as $term_id_chunk) {
      // Load terms for this batch.
      $terms = $term_storage->loadMultiple($term_id_chunk);

      /** @var \Drupal\taxonomy\TermInterface $term */
      foreach ($terms as $term) {
        if ($this->termMatchesPlacement($term, $placement_db_value)) {
          $filtered_categories[] = $term;
        }
      }
    }

    return $filtered_categories;
  }

  /**
   * Checks if a taxonomy term matches the given placement.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term to check.
   * @param string $placement_db_value
   *   The database value for the placement (e.g., 'node_topic', 'node_event').
   *
   * @return bool
   *   TRUE if the term matches the placement, FALSE otherwise.
   */
  private function termMatchesPlacement(TermInterface $term, string $placement_db_value): bool {
    if (!$term->isPublished()) {
      return FALSE;
    }

    // Check if term has the field_category_usage field.
    // Following the same pattern as TagCategoryAllowedContentTypes::resolve().
    if (!$term->hasField('field_category_usage')) {
      return FALSE;
    }

    $field = $term->get('field_category_usage');
    if ($field->isEmpty()) {
      return FALSE;
    }

    // Get the serialized placement values from the field.
    // The field is a string_long field containing a serialized array.
    $serialized_value = $field->value;
    if (empty($serialized_value)) {
      return FALSE;
    }

    // Unserialize the value.
    $usage_values = unserialize($serialized_value, ['allowed_classes' => FALSE]);
    if (!is_array($usage_values) || empty($usage_values)) {
      return FALSE;
    }

    // Check if the placement matches.
    // The values in the array are stored as 'node_topic', 'node_event', etc.
    foreach ($usage_values as $value) {
      if (!empty($value) && is_string($value)) {
        // Compare the database value directly.
        $value_trimmed = trim($value);
        if ($value_trimmed === $placement_db_value) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}

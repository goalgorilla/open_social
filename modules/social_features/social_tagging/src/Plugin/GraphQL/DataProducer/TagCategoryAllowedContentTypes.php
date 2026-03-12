<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\taxonomy\TermInterface;

/**
 * Returns the content types where a tag category can be used.
 *
 * @DataProducer(
 *   id = "tag_category_allowed_content_types",
 *   name = @Translation("Tag Category Allowed Content Types"),
 *   description = @Translation("Returns an array of content type identifiers (as GraphQL ENUM values like TOPIC, EVENT) where the given tag category can be used. Reads the category's field_category_usage field and converts internal database values (e.g., 'node_topic') to GraphQL ENUM values (e.g., 'TOPIC')."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Placement values")
 *   ),
 *   consumes = {
 *     "term" = @ContextDefinition("entity:taxonomy_term",
 *       label = @Translation("Term"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class TagCategoryAllowedContentTypes extends DataProducerPluginBase {

  /**
   * Resolves the placement values.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term.
   *
   * @return array
   *   Array of placement values mapped to GraphQL ENUM values.
   *   (e.g., 'TOPIC', 'EVENT').
   */
  public function resolve(TermInterface $term): array {
    // Get the field_category_usage field value.
    // The field is a string_long field containing a serialized array.
    if ($term->hasField('field_category_usage') && !$term->get('field_category_usage')->isEmpty()) {
      $serialized_value = $term->get('field_category_usage')->value;
      if (!empty($serialized_value)) {
        $usage_values = unserialize($serialized_value, ['allowed_classes' => FALSE]);
        if (is_array($usage_values)) {
          // Map database values to GraphQL ENUM values.
          // Database stores 'node_topic', 'node_event', etc.
          // GraphQL ENUM expects 'TOPIC', 'EVENT', etc.
          $placement_mapping = [
            'node_topic' => 'TOPIC',
            'node_event' => 'EVENT',
          ];

          $placements = [];
          foreach ($usage_values as $value) {
            if (!empty($value) && is_string($value)) {
              $value_trimmed = trim($value);
              // Check if we have a mapping for this value.
              if (isset($placement_mapping[$value_trimmed])) {
                $placements[] = $placement_mapping[$value_trimmed];
              }
            }
          }
          return $placements;
        }
      }
    }
    return [];
  }

}

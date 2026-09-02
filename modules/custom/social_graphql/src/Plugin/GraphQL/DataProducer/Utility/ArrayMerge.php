<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Utility;

use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Merges two arrays in order.
 *
 * @DataProducer(
 *   id = "array_merge",
 *   name = @Translation("Array merge"),
 *   description = @Translation("Merges two arrays in order."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Merged array"),
 *     multiple = TRUE
 *   ),
 *   consumes = {
 *     "first" = @ContextDefinition("any",
 *       label = @Translation("First array"),
 *       multiple = TRUE,
 *       required = FALSE
 *     ),
 *     "second" = @ContextDefinition("any",
 *       label = @Translation("Second array"),
 *       multiple = TRUE,
 *       required = FALSE
 *     )
 *   }
 * )
 */
class ArrayMerge extends DataProducerPluginBase {

  /**
   * Merges two arrays.
   *
   * @param array|null $first
   *   The first array.
   * @param array|null $second
   *   The second array.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $field
   *   The field context.
   *
   * @return array
   *   The merged array.
   */
  public function resolve(?array $first, ?array $second, FieldContext $field): array {
    return array_merge(
      is_array($first) ? $first : [],
      is_array($second) ? $second : [],
    );
  }

}

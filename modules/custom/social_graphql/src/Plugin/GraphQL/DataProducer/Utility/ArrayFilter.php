<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Utility;

use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Filters empty values from an array.
 *
 * @DataProducer(
 *   id = "array_filter",
 *   name = @Translation("Array filter"),
 *   description = @Translation("Filters NULL values from an array."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Filtered array"),
 *     multiple = TRUE
 *   ),
 *   consumes = {
 *     "array" = @ContextDefinition("any",
 *       label = @Translation("Input array"),
 *       multiple = TRUE,
 *       required = FALSE
 *     ),
 *     "preserve_keys" = @ContextDefinition("boolean",
 *       label = @Translation("Preserve keys"),
 *       required = FALSE,
 *       default_value = FALSE
 *     )
 *   }
 * )
 */
class ArrayFilter extends DataProducerPluginBase {

  /**
   * Filters an array.
   *
   * @param array|null $array
   *   The input array.
   * @param bool|null $preserve_keys
   *   Whether to preserve array keys.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $field
   *   The field context.
   *
   * @return array
   *   The filtered array.
   */
  public function resolve(?array $array, ?bool $preserve_keys, FieldContext $field): array {
    if (!is_array($array)) {
      return [];
    }

    $filtered = array_filter(
      $array,
      static fn ($item) => $item !== NULL,
    );

    if ($preserve_keys) {
      return $filtered;
    }

    return array_values($filtered);
  }

}

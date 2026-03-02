<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns the value at a key in an array.
 *
 * Use for resolving fields from a parent array when property_path does not
 * apply (e.g. plain arrays).
 *
 * @DataProducer(
 *   id = "map_key",
 *   name = @Translation("Map key"),
 *   description = @Translation("Returns the value at the given key in the parent array."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Value"),
 *     required = FALSE
 *   ),
 *   consumes = {
 *     "value" = @ContextDefinition("any",
 *       label = @Translation("Array or array-like value")
 *     ),
 *     "key" = @ContextDefinition("string",
 *       label = @Translation("Key")
 *     ),
 *   }
 * )
 */
class MapKey extends DataProducerPluginBase {

  /**
   * Resolves the value at the given key.
   *
   * @param mixed $value
   *   The parent value (typically an array from entity_address).
   * @param string $key
   *   The key to look up.
   *
   * @return mixed
   *   The value at that key, or NULL if not an array or key missing.
   */
  public function resolve($value, string $key) {
    if (!is_array($value)) {
      return NULL;
    }
    return $value[$key] ?? NULL;
  }

}

<?php

namespace Drupal\social_graphql\GraphQL;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry as ResolverRegistryBase;

/**
 * The Open Social resolver registry.
 *
 * Extends the base ResolverRegistry to provide a way to implement common
 * helpers.
 *
 * Previously included resolver inheritance which has been moved into the
 * GraphQL module.
 */
class ResolverRegistry extends ResolverRegistryBase {

  /**
   * Add a field resolver for a mutation field.
   *
   * Calling this function is equivalent to writing out the following code
   * manually:
   *
   * ```
   * $field_name = camelCaseTOsnake_case($fieldName);
   * $this->addFieldResolver('Mutation', $fieldName,
   *   $builder->compose(
   *     $builder->produce($field_name . "_input")
   *       ->map('input', $builder->fromArgument('input')),
   *     $builder->produce($field_name)
   *       ->map('input', $builder->fromParent())
   *   )
   * );
   * ```
   *
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   * @param string $field_name
   *   A camelCased field name. This will be converted into snake_case for the
   *   IDs of the used data producers.
   */
  public function addMutationResolver(ResolverBuilder $builder, string $field_name) : void {
    $words = array_map(
      'strtolower',
      preg_split('/(?=[A-Z])/', $field_name, -1, PREG_SPLIT_NO_EMPTY)
    );
    $data_producer = implode("_", $words);

    $this->addFieldResolver('Mutation', $field_name,
      $builder->compose(
        $builder->produce($data_producer . "_input")
          ->map('input', $builder->fromArgument('input')),
        $builder->produce($data_producer)
          ->map('input', $builder->fromParent())
      )
    );
  }

}

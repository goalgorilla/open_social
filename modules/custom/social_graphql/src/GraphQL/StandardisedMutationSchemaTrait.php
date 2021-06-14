<?php

namespace Drupal\social_graphql\GraphQL;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * Provides a way for schemas to add mutations in a standardised format.
 */
trait StandardisedMutationSchemaTrait {

  /**
   * Add a field resolver for a mutation field.
   *
   * Calling this function is equivalent to writing out the following code
   * manually:
   *
   * ```
   * $field_name = camelCaseTOsnake_case($fieldName);
   * $registry->addFieldResolver('Mutation', $fieldName,
   *   $builder->compose(
   *     $builder->produce($field_name . "_input")
   *       ->map('input', $builder->fromArgument('input')),
   *     $builder->produce($field_name)
   *       ->map('input', $builder->fromParent())
   *   )
   * );
   * ```
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The registry in which to register the mutation.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   * @param string $field_name
   *   A camelCased field name. This will be converted into snake_case for the
   *   IDs of the used data producers.
   */
  protected function registerMutationResolver(ResolverRegistryInterface $registry, ResolverBuilder $builder, string $field_name) : void {
    $words = array_map(
      'strtolower',
      preg_split('/(?=[A-Z])/', $field_name, -1, PREG_SPLIT_NO_EMPTY)
    );
    $data_producer = implode("_", $words);

    $registry->addFieldResolver('Mutation', $field_name,
      $builder->compose(
        $builder->produce($data_producer . "_input")
          ->map('input', $builder->fromArgument('input')),
        $builder->produce($data_producer)
          ->map('input', $builder->fromParent())
      )
    );
  }

}

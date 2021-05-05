<?php

namespace Drupal\social_graphql\GraphQL;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry as ResolverRegistryBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * The Open Social resolver registry.
 *
 * Extends the base ResolverRegistry from GraphQL to try to resolve fields on
 * implemented interfaces when a field resolver for the type isn't found.
 */
class ResolverRegistry extends ResolverRegistryBase {

  /**
   * Return all field resolvers in the registry.
   *
   * @return callable[]
   *   A nested list of callables, keyed by type and field name.
   */
  public function getAllFieldResolvers() : array {
    return $this->fieldResolvers;
  }

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

  /**
   * {@inheritdoc}
   */
  protected function getRuntimeFieldResolver($value, $args, ResolveContext $context, ResolveInfo $info) {
    // Try the default implementation but fallback to trying the interfaces.
    return parent::getRuntimeFieldResolver($value, $args, $context, $info)
      ?? $this->getRuntimeFieldResolverOnInterface($value, $args, $context, $info);
  }

  /**
   * Attempts to find a resolver on an implemented interface.
   *
   * @param mixed $value
   *   Value passed by getRuntimeFieldResolver.
   * @param string $args
   *   Args passed by getRuntimeFieldResolver.
   * @param \Drupal\graphql\GraphQL\Execution\ResolveContext $context
   *   Context passed by getRuntimeFieldResolver.
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *   Info passed by getRuntimeFieldResolver.
   *
   * @return callable|null
   *   The resolver for the field or null if none was found.
   */
  protected function getRuntimeFieldResolverOnInterface($value, $args, ResolveContext $context, ResolveInfo $info) {
    // Go through the interfaces implemented for the type on which this field is
    // resolved and check if they lead to a field resolution.
    $interfaces = $info->parentType->getInterfaces();
    foreach ($interfaces as $interface) {
      if ($resolver = $this->getFieldResolver($interface->name, $info->fieldName)) {
        return $resolver;
      }
    }

    return NULL;
  }

}

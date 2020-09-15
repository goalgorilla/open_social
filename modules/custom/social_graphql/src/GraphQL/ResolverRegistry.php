<?php

namespace Drupal\social_graphql\GraphQL;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
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
   * @param string $args
   * @param \Drupal\graphql\GraphQL\Execution\ResolveContext $context
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *
   * @return callable|null
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

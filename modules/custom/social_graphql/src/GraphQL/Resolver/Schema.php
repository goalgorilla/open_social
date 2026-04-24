<?php

declare(strict_types=1);

namespace Drupal\social_graphql\GraphQL\Resolver;

use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\Resolver\ResolverInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema as SchemaType;

/**
 * Resolves to the schema for the current request.
 *
 * Provides a workaround for the GraphQL module not providing `ResolveInfo` to
 * `resolve` functions that are produced through the DataProducerProxy class.
 *
 * @internal
 */
class Schema implements ResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(mixed $value, array $args, ResolveContext $context, ResolveInfo $info, FieldContext $field): SchemaType {
    return $info->schema;
  }

}

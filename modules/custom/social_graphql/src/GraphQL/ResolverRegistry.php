<?php

declare(strict_types=1);

namespace Drupal\social_graphql\GraphQL;

use Drupal\graphql\GraphQL\ResolverRegistry as ResolverRegistryBase;

/**
 * The Open Social resolver registry.
 *
 * Extends the base ResolverRegistry to provide a way to implement common
 * helpers and to register custom scalars.
 *
 * Previously included resolver inheritance which has been moved into the
 * GraphQL module.
 */
class ResolverRegistry extends ResolverRegistryBase {

}

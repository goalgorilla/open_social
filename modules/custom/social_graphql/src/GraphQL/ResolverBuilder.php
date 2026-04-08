<?php

declare(strict_types=1);

namespace Drupal\social_graphql\GraphQL;

use Drupal\graphql\GraphQL\ResolverBuilder as ResolverBuilderBase;
use Drupal\social_graphql\GraphQL\Resolver\Schema;

/**
 * Provides a Resolver Builder internal to Open Social.
 *
 * @internal
 */
class ResolverBuilder extends ResolverBuilderBase {

  /**
   * Produce the schema as a value.
   *
   * @return \Drupal\social_graphql\GraphQL\Resolver\Schema
   *   The resolver instance.
   */
  public function schema() : Schema {
    return new Schema();
  }

}

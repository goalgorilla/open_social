<?php

namespace Drupal\social_user\GraphQL;

use Drupal\social_graphql\GraphQL\DecoratableTypeResolver;
use Drupal\user\UserInterface;

/**
 * Type resolver for User concrete class of Actor interface.
 */
class UserActorTypeResolver extends DecoratableTypeResolver {

  /**
   * {@inheritdoc}
   */
  protected function resolve($actor) : ?string {
    return $actor instanceof UserInterface ? 'User' : NULL;
  }

}

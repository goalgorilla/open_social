<?php

namespace Drupal\social_user\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\user\UserInterface;

/**
 * Returns the roles for a user.
 *
 * @DataProducer(
 *   id = "user_roles",
 *   name = @Translation("User roles"),
 *   description = @Translation("Returns the roles that a user has."),
 *   produces = @ContextDefinition("array",
 *     label = @Translation("User roles")
 *   ),
 *   consumes = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     )
 *   }
 * )
 */
class UserRoles extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value for this data producer.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to get the roles for.
   *
   * @return string[]
   *   The roles the user has.
   */
  public function resolve(UserInterface $user) {
    return $user->getRoles();
  }

}

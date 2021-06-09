<?php

namespace Drupal\social_user\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_user\Wrappers\UserAwareInterface;
use Drupal\user\UserInterface;

/**
 * Get the user information from a relationship.
 *
 * @DataProducer(
 *   id = "user_from_wrapper",
 *   name = @Translation("User from data structure"),
 *   description = @Translation("The user information for a UserAwareInterface implementing type."),
 *   produces = @ContextDefinition("entity:user",
 *     label = @Translation("User entity")
 *   ),
 *   consumes = {
 *     "data" = @ContextDefinition("any",
 *       label = @Translation("Data"),
 *       description = @Translation("A class instance containing user information."),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class UserFromWrapper extends DataProducerPluginBase {

  /**
   * Resolves the value.
   *
   * @param \Drupal\social_user\Wrappers\UserAwareInterface $data
   *   A class that contains user information.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user entity.
   */
  public function resolve(UserAwareInterface $data) : ?UserInterface {
    return $data->getUser();
  }

}

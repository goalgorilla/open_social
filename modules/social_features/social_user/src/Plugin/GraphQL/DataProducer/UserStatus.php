<?php

namespace Drupal\social_user\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\user\UserInterface;

/**
 * Returns the status for a user.
 *
 * @DataProducer(
 *   id = "user_status",
 *   name = @Translation("User status"),
 *   description = @Translation("Returns the status of the user."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("User status")
 *   ),
 *   consumes = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     )
 *   }
 * )
 */
class UserStatus extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value for this data producer.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to get the status from.
   *
   * @return string
   *   The status of the user ("ACTIVE" or "BLOCKED").
   */
  public function resolve(UserInterface $user) {
    return $user->isActive() ? "ACTIVE" : "BLOCKED";
  }

}

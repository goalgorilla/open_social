<?php

namespace Drupal\social_user\Wrappers;

use Drupal\user\UserInterface;

/**
 * Provides a common interface for data that may contain a user entity.
 */
interface UserAwareInterface {

  /**
   * Return the user information.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user entity.
   */
  public function getUser() : ?UserInterface;

}

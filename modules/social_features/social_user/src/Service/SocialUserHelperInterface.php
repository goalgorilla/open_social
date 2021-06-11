<?php

namespace Drupal\social_user\Service;

use Drupal\user\UserInterface;

/**
 * Defines the helper service interface.
 *
 * @package Drupal\social_user\Service;
 */
interface SocialUserHelperInterface {

  /**
   * Check if a user is verified.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return bool
   *   TRUE if the user is verified, FALSE otherwise.
   */
  public function isVerifiedUser(UserInterface $account): bool;

  /**
   * Verified user roles.
   *
   * @return string[]
   *   List of verified user roles.
   */
  public function verifiedUserRoles(): array;

}

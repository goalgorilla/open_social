<?php

namespace Drupal\social_user\Service;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the helper service interface.
 *
 * @package Drupal\social_user\Service;
 */
interface SocialUserHelperInterface {

  /**
   * Check if a user is verified.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return bool
   *   TRUE if the user is verified, FALSE otherwise.
   */
  public static function isVerifiedUser(AccountInterface $account): bool;

  /**
   * Verified user roles.
   *
   * @return mixed[]
   *   List of verified user roles.
   */
  public static function verifiedUserRoles(): array;

}

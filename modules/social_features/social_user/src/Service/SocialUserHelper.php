<?php

namespace Drupal\social_user\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleInterface;

/**
 * Provides helper functions for Social users.
 *
 * @package Drupal\social_user
 */
class SocialUserHelper implements SocialUserHelperInterface {

  /**
   * {@inheritdoc}
   */
  public static function isVerifiedUser(AccountInterface $account): bool {
    // Verified user roles.
    $roles = SocialUserHelper::verifiedUserRoles();

    // Get user roles.
    $user_roles = $account->getRoles();

    // User is a Verified if has roles in addition to 'authenticated'.
    return (bool) array_intersect($roles, $user_roles);
  }

  /**
   * {@inheritdoc}
   */
  public static function verifiedUserRoles(): array {
    // Get all exist roles except 'anonymous'.
    $roles = array_map(function (RoleInterface $role) {
      return $role->id();
    }, user_roles(TRUE));

    // Remove the 'authenticated' role.
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    return $roles;
  }

}

<?php

namespace Drupal\social_follow_user;

use Drupal\user\RoleInterface;

/**
 * Defines a helper class, sets permissions.
 *
 * @package Drupal\social_follow_user
 */
class SocialFollowUserHelper {

  /**
   * Set default permissions.
   */
  public static function setPermissions(): void {
    $roles = \Drupal::entityQuery('user_role')
      ->condition('id', 'administrator', '<>')
      ->execute();

    foreach ($roles as $role) {
      user_role_grant_permissions($role, self::getPermissions($role));
    }
  }

  /**
   * Set default permissions.
   */
  public static function getPermissions(string $role): array {
    // Anonymous.
    $permissions[RoleInterface::ANONYMOUS_ID] = [];

    // Authenticated.
    $permissions[RoleInterface::AUTHENTICATED_ID] = array_merge($permissions[RoleInterface::ANONYMOUS_ID], [
      'flag follow_user',
      'unflag follow_user',
    ]);

    // Content manager.
    $permissions['contentmanager'] = array_merge($permissions[RoleInterface::AUTHENTICATED_ID], []);

    // Site manager.
    $permissions['sitemanager'] = array_merge($permissions['contentmanager'], []);

    return $permissions[$role] ?? [];
  }

}

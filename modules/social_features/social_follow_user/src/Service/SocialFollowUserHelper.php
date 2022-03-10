<?php

namespace Drupal\social_follow_user\Service;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the helper service.
 */
class SocialFollowUserHelper implements SocialFollowUserHelperInterface {

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

  /**
   * {@inheritdoc}
   */
  public function preview(
    ProfileInterface $profile,
    array &$variables,
    $path = 'attributes'
  ): void {
    if ($profile->access('view')) {
      if (!NestedArray::keyExists($variables, $path = (array) $path)) {
        NestedArray::setValue($variables, $path, []);
      }

      $attributes = &NestedArray::getValue($variables, $path);

      $attributes['id'] = Html::getUniqueId('profile-preview');
      $attributes['data-profile'] = $profile->id();

      $variables['#attached']['library'][] = 'social_follow_user/preview';
    }
  }

}

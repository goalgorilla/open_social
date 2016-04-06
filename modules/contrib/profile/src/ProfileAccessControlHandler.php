<?php

/**
 * @file
 * Contains \Drupal\profile\ProfileAccessControlHandler.
 */

namespace Drupal\profile;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\profile\Entity\ProfileType;

/**
 * Defines the access control handler for the profile entity type.
 *
 * @see \Drupal\profile\Entity\Profile
 */
class ProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('bypass profile access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   *
   * When the $operation is 'add' then the $entity is of type 'profile_type',
   * otherwise $entity is of type 'profile'.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);

    $user_page = \Drupal::request()->attributes->get('user');

    // Some times, operation edit is called update.
    // Use edit in any case.
    if ($operation == 'update') {
      $operation = 'edit';
    }

    // Check that if profile type has require roles, the user the profile is
    // being added to has any of the required roles.
    if ($entity->getEntityTypeId() == 'profile') {
      $profile_roles = ProfileType::load($entity->bundle())->getRoles();
      $user_roles = $entity->getOwner()->getRoles(TRUE);
      if (!empty(array_filter($profile_roles)) && !array_intersect($user_roles, $profile_roles)) {
        return AccessResult::forbidden();
      }
    }
    elseif ($entity->getEntityTypeId() == 'profile_type') {
      $profile_roles = $entity->getRoles();
      $user_roles = User::load($user_page->id())->getRoles(TRUE);
      if (!empty(array_filter($profile_roles)) && !array_intersect($user_roles, $profile_roles)) {
        return AccessResult::forbidden();
      }
    }

    if ($account->hasPermission('bypass profile access')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    elseif (
      (
        $operation == 'add'
        && (
          (
            $user_page->id() == $account->id()
            && $account->hasPermission($operation . ' own ' . $entity->id() . ' profile')
          )
          || $account->hasPermission($operation . ' any ' . $entity->id() . ' profile')
        )
      ) || (
        $operation != 'add'
        && (
          (
            $entity->getOwnerId() == $account->id()
            && $account->hasPermission($operation . ' own ' . $entity->getType() . ' profile')
          )
          || $account->hasPermission($operation . ' any ' . $entity->getType() . ' profile')
        )
      )
    ){
      return AccessResult::allowed()->cachePerPermissions();
    }
    else {
      return AccessResult::forbidden()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'add any ' . $entity_bundle . ' profile',
      'add own ' . $entity_bundle . ' profile',
    ], 'OR');
  }

}

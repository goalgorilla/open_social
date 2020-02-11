<?php

namespace Drupal\social_user;

use Drupal\Core\Session\AccountInterface;

/**
 * Class UserRoleService.
 */
class UserRoleHelper {

  /**
   * Adjusts the role grant/revoke options.
   *
   * @param array $roleOptions
   *   The array containing role options.
   * @param string $accessRole
   *   The role the user needs to $revokeOrGrantRole from $roleOptions.
   * @param array $rolesToAddOrRemove
   *   The role that needs to be removed from the options.
   * @param bool $remove
   *   If FALSE, we add the role. Otherwise it will be removed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The given account.
   *
   * @return array
   *   The array containing the adjusted role options.
   */
  public static function alterAddOrRemoveRoleOptions(array $roleOptions, $accessRole = NULL, array $rolesToAddOrRemove = [], $remove = TRUE, AccountInterface $account = NULL) {
    // Get the user account and roles, if no user is given we use the current.
    $userRoles = (NULL !== $account ? $account->getRoles(TRUE) : \Drupal::currentUser()->getRoles(TRUE));
    // Check if the user has the correct $accessRole in his or her roles,
    // then adjust accordingly.
    if (in_array($accessRole, $userRoles) && !in_array('administrator', $userRoles)) {
      foreach ($rolesToAddOrRemove as $role) {
        if ($remove === FALSE) {
          $roleOptions[$role];
        }
        elseif ($remove === TRUE) {
          unset($roleOptions[$role]);
        }
      }
    }

    return $roleOptions;
  }

}

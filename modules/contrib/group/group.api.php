<?php

/**
 * @file
 * Hooks specific to the Group module.
 */

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of roles a user gets for a group.
 *
 * Please keep in mind this only applies to roles retrieved by the GroupRole
 * storage handler's loadByUserAndGroup() method. Group will always use this
 * internally, but extending modules must make sure they also retrieve a list
 * of roles that way when the list should be alterable.
 *
 * Also note that any role you add to the list must be valid for the group's
 * type. If you add roles that are not available to the group type, these will
 * just be filtered out before any permission checks run.
 *
 * Warning: For stability's sake, this function will not be passed in any of the
 * special roles 'anonymous', 'outsider' or 'member'. Those will be added in
 * after this alter hook.
 *
 * @param string[] $role_ids
 *   The list of GroupRole IDs the user would normally get.
 * @param Drupal\Core\Session\AccountInterface $account
 *   The user who is to receive the roles.
 * @param Drupal\group\Entity\GroupInterface $group
 *   The group for which the user is to receive the roles.
 *
 * @see \Drupal\group\Entity\Storage\GroupRoleStorage::loadByUserAndGroup()
 * @ingroup group_access
 */
function hook_group_user_roles_alter(&$role_ids, AccountInterface $account, GroupInterface $group) {
  if ($account->hasPermission('view any group')) {
    $role_ids[] = $group->bundle() . '-viewer';
  }
}

/**
 * @} End of "addtogroup hooks".
 */

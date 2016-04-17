<?php

/**
 * @file
 * Hooks provided by profile module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Control access to a user profile.
 *
 * Modules may implement this hook to control whether a user has access to
 * perform a certain operation on a profile.
 *
 * @param string $op
 *   The operation being performed. One of 'view', 'edit' (being the same as
 *   'create' or 'update'), or 'delete'.
 * @param Drupal\profile\Entity\Profile $profile
 *   A profile to check access for.
 * @param Drupal\user\Entity\User $account
 *   The user performing the operation; the currently logged in user by default.
 *
 * @return bool
 *   Either a Boolean or NULL:
 *   - FALSE to explicitly deny access. If a module denies access, no other
 *     module is able to grant access and access is denied.
 *   - TRUE to grant access. Access is only granted if at least one module
 *     grants access and no module denies access.
 *   - NULL or nothing to not affect the operation. If no module explicitly
 *     grants access, access is denied.
 */
function hook_profile_access($op, Drupal\profile\Entity\Profile $profile, Drupal\user\Entity\User $account) {
  // Explicitly deny access for a 'secret' profile type.
  if ($profile->getType() == 'secret' && !\Drupal::currentUser()->hasPermission('custom permission')) {
    return FALSE;
  }
  // For profiles other than the default profile grant access.
  if ($profile->getType() != 'main' && \Drupal::currentUser()->hasPermission('custom permission')) {
    return TRUE;
  }
  // In other cases do not alter access.
  return NULL;
}

/**
 * @}
 */

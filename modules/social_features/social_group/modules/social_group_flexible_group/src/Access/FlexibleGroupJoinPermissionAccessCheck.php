<?php

namespace Drupal\social_group_flexible_group\Access;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access based on permissions added to the Join method options.
 */
class FlexibleGroupJoinPermissionAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $permission = $route->getRequirement('_group_permission');

    // Don't interfere if no permission was specified.
    if ($permission === NULL) {
      return AccessResult::neutral();
    }

    // Don't interfere if no group was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group')) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::neutral();
    }

    $type = $group->getGroupType();
    // Don't interfere if the group isn't a flexible group.
    if ($type instanceof GroupTypeInterface && $type->id() !== 'flexible_group') {
      return AccessResult::neutral();
    }

//    $fields_to_check = [
//      'field_group_allowed_join_method',
//      'field_group_allowed_visibility',
//    ];
    // List of permissions affected by the join method.
    $permissions_list = [
      'join group',
      'view group_membership content',
      'administer members',
      'view group',
      'view group stream page',
    ];

    // Not one of our permissions being checked so we don't care.
    if (!in_array($permission, $permissions_list, FALSE)) {
      return AccessResult::neutral();
    }

    // AN Users aren't allowed anything.
    if (!$account->isAuthenticated()) {
      return AccessResult::forbidden();
    }

    // Outsider LU are only allowed when Direct is an option.
    $allowed = $this->calculateJoinPermission($permission, $group, $account);
    if (!$allowed) {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    // We allow it but lets add the group as dependency to the cache
    // now because field value might be editted and cache should
    // clear accordingly.
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, $permission)
      ->addCacheableDependency($group);
  }

  /**
   * Calculates permissions for LU users also including administrative roles.
   *
   * @param string $permission
   *   The permission we need to check access for.
   * @param \Drupal\group\Entity\Group $group
   *   The Group we are on.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return bool
   *   FALSE if its not allowed.
   */
  private function calculateJoinPermission($permission, Group $group, AccountInterface $account) {
    $direct_option = social_group_flexible_group_can_join_directly($group);

    // There is no direct join method so it's not allowed to go to /join.
    if ($permission === 'join group' && !$direct_option) {
      return FALSE;
    }

    // Access to the group information || group stream page.
    if (($permission === 'view group' || $permission === 'view group stream page') && !$direct_option) {
      return FALSE;
    }

    return TRUE;
  }

}

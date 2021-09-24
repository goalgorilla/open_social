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
 * Determines access to routes based flexible_group membership and settings.
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
    $permission = $route->getRequirement('_flexible_group_join_permission');
    $group_permission = $route->getRequirement('_group_permission');

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
      $group = _social_group_get_current_group();
      if (!$group instanceof GroupInterface) {
        return AccessResult::neutral();
      }
    }

    $type = $group->getGroupType();
    // Don't interfere if the group isn't a flexible group.
    if ($type instanceof GroupTypeInterface && $type->id() !== 'flexible_group') {
      if (!empty($group_permission)) {
        return GroupAccessResult::allowedIfHasGroupPermissions($group, $account, [$group_permission]);
      }

      // We need this fallback for SM/CM.
      // Neutral will break because the manage tab doesn't work with
      // group permission but with general permissions.
      $condition1 = $account->hasPermission('manage all groups');
      $condition2 = $group->hasPermission('administer members', $account);
      return AccessResult::allowedIf($condition1 || $condition2)->addCacheableDependency($group);
    }

    // GM is allowed to go to Add Directly page, adding new members directly.
    if ($permission === 'join added' && $group->hasPermission('administer members', $account)) {
      return AccessResult::allowed()->addCacheableDependency($group);
    }

    // A user with this access can definitely do everything.
    if ($account->hasPermission('manage all groups')) {
      return AccessResult::allowed()->addCacheableDependency($group);
    }

    // AN Users aren't allowed anything.
    if (!$account->isAuthenticated()) {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    // Outsider LU are only allowed when Direct is an option.
    $allowed = $this->calculateJoinPermission($permission, $group, $account, $route_match);
    if (!$allowed) {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    // We allow it but lets add the group as dependency to the cache
    // now because field value might be editted and cache should
    // clear accordingly.
    if (!empty($group_permission)) {
      return GroupAccessResult::allowedIfHasGroupPermissions($group, $account, [$group_permission])->addCacheableDependency($group);
    }

    return AccessResult::allowed()->addCacheableDependency($group);
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return bool
   *   FALSE if its not allowed.
   */
  private function calculateJoinPermission($permission, Group $group, AccountInterface $account, RouteMatchInterface $route_match) {
    $direct_option = social_group_flexible_group_can_join_directly($group);
    $added_option = social_group_flexible_group_can_be_added($group);

    // Users with this permission are always able to do so.
    if ($account->hasPermission('manage all groups')) {
      return TRUE;
    }

    // LU Can only see members tabs for joining directly
    // or when they are a GM/GA.
    if (!$direct_option &&
      $route_match->getRouteName() === 'view.group_manage_members.page_group_manage_members' &&
      $account->isAuthenticated() &&
      !$group->getMember($account) &&
      !$group->hasPermission('administer members', $account)) {
      return FALSE;
    }

    // There is no direct join method so it's not allowed to go to /join.
    if ($permission === 'join direct' && !$direct_option && !$group->getMember($account)) {
      return FALSE;
    }

    if ($permission === 'join added' && !$added_option) {
      return FALSE;
    }

    return TRUE;
  }

}

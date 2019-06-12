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
    $permissions = [
      'join group',
      'view group_membership content',
      'administer members',
      'view group',
    ];

    // @TODO implement hook so we can add or remove permissions from this list.
    // Check all permissions that could be affected.
    foreach ($permissions as $affected_permission) {
      if ($permission !== $affected_permission) {
        // Not one of our permissions so we don't really care.
        // Allow to conjunct the permissions with OR ('+') or AND (',').
        return AccessResult::neutral();
      }
    }

    // Lets grab all possible join methods.
    $join_methods = $group->get('field_group_allowed_join_method')->getValue();

    // This permission is affected, deny access to all users if there is no
    // join method set.
    if ($join_methods === NULL) {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    // AN Users aren't allowed anything.
    if (!$account->isAuthenticated()) {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    $direct_as_option = in_array('direct', array_column($join_methods, 'value'), FALSE);
//    $added_option = in_array('added', array_column($join_methods, 'value'), FALSE);

    // Outsider LU are only allowed when Direct is an option.
    if (!$direct_as_option && $account->isAuthenticated()) {
      $allowed = $this->calculateOutsiderPermission($permission, $group, $account);

      if (!$allowed) {
        return AccessResult::forbidden()->addCacheableDependency($group);
      }
    }

    // We allow it but lets add the group as dependency to the cache
    // now because field value might be editted and cache should
    // clear accordingly.
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
   *
   * @return bool
   *   FALSE if its not allowed.
   */
  private function calculateOutsiderPermission($permission, Group $group, AccountInterface $account) {
    // User has administrative permissions so we can allow it.
    // Mostly SM+.
    if (($permission === 'administer members' || $permission === 'view group_membership content')
        && $account->hasPermission('administer members')) {
      return TRUE;
    }
    // User is already a member lets not give them access to join again.
    if ($permission === 'join group' && !$group->getMember($account)) {
      return TRUE;
    }

    if ($permission === 'view group') {
      return TRUE;
    }

    return FALSE;
  }

}

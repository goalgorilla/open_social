<?php

namespace Drupal\social_group_flexible_group\Access;

use Drupal\group\Access\GroupAccessResult;
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
class FlexibleGroupContentAccessCheck implements AccessInterface {

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
    $permission = $route->getRequirement('_flexible_group_content_visibility');
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
      return AccessResult::neutral();
    }

    // A user with this access can definitely do everything.
    if ($account->hasPermission('manage all groups')) {
      return AccessResult::allowed();
    }

    $type = $group->getGroupType();
    // Don't interfere if the group isn't a flexible group.
    if ($type instanceof GroupTypeInterface && $type->id() !== 'flexible_group') {
      if (!empty($group_permission)) {
        GroupAccessResult::allowedIfHasGroupPermissions($group, $account, [$group_permission]);
      }
      return AccessResult::allowedIf(TRUE);
    }

    // AN Users aren't allowed anything if Public isn't an option.
    if (!$account->isAuthenticated() && !social_group_flexible_group_public_enabled($group)) {
      return AccessResult::forbidden();
    }

    // If User is a member we can also rely on Group to take permissions.
    if ($group->getMember($account) !== FALSE) {
      return AccessResult::allowed()->addCacheableDependency($group);
    }

    // It's a non member but Community isn't enabled.
    // No access for you only for the about page.
    if ($account->isAuthenticated() && !social_group_flexible_group_community_enabled($group)
      && $route_match->getRouteName() !== 'view.group_information.page_group_about'
      && $route_match->getRouteName() !== 'entity.group.canonical') {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    // We allow it but lets add the group as dependency to the cache
    // now because field value might be edited and cache should
    // clear accordingly.
    return AccessResult::allowed()->addCacheableDependency($group);
  }

}

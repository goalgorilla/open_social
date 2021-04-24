<?php

namespace Drupal\social_group_flexible_group\Access;

use Drupal\group\Entity\Group;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupMembership;
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

    // Don't interfere if no permission was specified.
    if ($permission === NULL) {
      return AccessResult::allowed();
    }

    // Don't interfere if no group was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group')) {
      return AccessResult::allowed();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');
    if (!$group instanceof Group) {
      return AccessResult::allowed();
    }

    // Handling the visibility of a group.
    if ($group->hasField('field_flexible_group_visibility')) {
      $group_visibility_value = $group->getFieldValue('field_flexible_group_visibility', 'value');
      $is_member = $group->getMember($account) instanceof GroupMembership;

      switch ($group_visibility_value) {
        case 'members':
          if (!$is_member) {
            return AccessResult::forbidden();
          }
          break;

        case 'community':
          if ($account->isAnonymous()) {
            return AccessResult::forbidden();
          }
          break;
      }
    }

    $type = $group->getGroupType();
    // Don't interfere if the group isn't a flexible group.
    if ($type instanceof GroupTypeInterface && $type->id() !== 'flexible_group') {
      return AccessResult::allowed();
    }

    // A user with this access can definitely do everything.
    if ($account->hasPermission('manage all groups')) {
      return AccessResult::allowed();
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
      && !social_group_flexible_group_public_enabled($group)
      && $route_match->getRouteName() !== 'view.group_information.page_group_about'
      && $route_match->getRouteName() !== 'entity.group.canonical'
      && $route_match->getRouteName() !== 'view.group_members.page_group_members') {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    // We allow it but lets add the group as dependency to the cache
    // now because field value might be edited and cache should
    // clear accordingly.
    return AccessResult::allowed()->addCacheableDependency($group);
  }

}

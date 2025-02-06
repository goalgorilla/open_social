<?php

namespace Drupal\social_group_flexible_group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\social_group\SocialGroupInterface;
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
    if (!$group instanceof SocialGroupInterface) {
      return AccessResult::allowed();
    }

    // A user with this access can definitely do everything.
    if ($account->hasPermission('manage all groups')) {
      return AccessResult::allowed();
    }

    $is_member = $group->hasMember($account);

    if (!$group->hasField('field_flexible_group_visibility')) {
      // Group must have a visibility field.
      return AccessResult::allowed();
    }

    $visibility = $group->get('field_flexible_group_visibility')->getString();

    switch ($visibility) {
      case 'members':
        if (!$is_member || !$account->hasPermission("view members {$group->bundle()} group")) {
          return AccessResult::forbidden();
        }
        break;

      case 'community':
        if (!$account->hasPermission("view community {$group->bundle()} group")) {
          return AccessResult::forbidden();
        }
        break;
    }

    $type = $group->getGroupType();
    // Don't interfere if the group isn't a flexible group.
    if ($type instanceof GroupTypeInterface && $type->id() !== 'flexible_group') {
      return AccessResult::allowed();
    }

    // AN Users aren't allowed anything if Public isn't an option.
    if (!$account->isAuthenticated() && !social_group_flexible_group_public_enabled($group)) {
      return AccessResult::forbidden();
    }

    // If User is a member we can also rely on Group to take permissions.
    if ($is_member) {
      return AccessResult::allowed()->addCacheableDependency($group);
    }

    // It's a non-member but "community" isn't enabled.
    // No access for you only for the about page.
    if (
      $visibility !== 'public' &&
      !$account->hasPermission("view $visibility {$group->bundle()} group")
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

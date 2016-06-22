<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupOwnsContentAccessCheck.
 */

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\Core\Access\AccessResult;
// @todo Follow up on https://www.drupal.org/node/2266817.
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on whether a piece of group content belongs
 * to the group that was also specified in the route.
 */
class GroupOwnsContentAccessCheck implements AccessInterface {

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
    $must_own_content = $route->getRequirement('_group_owns_content') === 'TRUE';

    // Don't interfere if no group or group content was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group') || !$parameters->has('group_content')) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group content isn't a real group content entity.
    $group_content = $parameters->get('group_content');
    if (!$group_content instanceof GroupContentInterface) {
      return AccessResult::neutral();
    }

    // If we have a group and group content, see if the owner matches.
    $group_owns_content = $group_content->getGroup()->id() == $group->id();

    // Only allow access if the group content is owned by the group and
    // _group_owns_content is set to TRUE or the other way around.
    return AccessResult::allowedIf($group_owns_content xor !$must_own_content);
  }

}

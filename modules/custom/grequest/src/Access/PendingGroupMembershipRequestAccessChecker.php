<?php

namespace Drupal\grequest\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Symfony\Component\Routing\Route;

/**
 * Checks access for group membership request approval / rejection pages.
 */
class PendingGroupMembershipRequestAccessChecker implements AccessInterface {

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
    // Check if route requires this checker.
    $requirement = $route->getRequirement('_pending_group_membership_request');

    // Don't interfere if no requirement was specified.
    if ($requirement !== 'TRUE') {
      return AccessResult::neutral();
    }

    if (($route_object = $route_match->getRouteObject()) &&
      ($route_contexts = $route_object->getOption('parameters')) &&
      !empty($route_contexts['group_content']) &&
      ($group_membership_request = $route_match->getParameter('group_content'))
    ) {
      if ($group_membership_request->get(GroupMembershipRequest::STATUS_FIELD)->value == GroupMembershipRequest::REQUEST_PENDING) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}

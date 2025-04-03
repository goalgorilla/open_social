<?php

namespace Drupal\grequest\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\MembershipRequestManager;
use Symfony\Component\Routing\Route;

/**
 * Checks access for group membership request approval / rejection pages.
 */
class GroupMembershipRequestAccessChecker implements AccessInterface {

  /**
   * Group request manager.
   *
   * @var \Drupal\grequest\MembershipRequestManager
   */
  protected $groupRequestManager;

  /**
   * Access Checker constructor.
   *
   * @param \Drupal\grequest\MembershipRequestManager $group_request_manager
   *   Group request manager.
   */
  public function __construct(MembershipRequestManager $group_request_manager) {
    $this->groupRequestManager = $group_request_manager;
  }

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
    $requirement = $route->getRequirement('_group_membership_request');

    // Don't interfere if no requirement was specified.
    if ($requirement !== 'TRUE') {
      return AccessResult::neutral();
    }

    if (($route_object = $route_match->getRouteObject()) &&
      ($route_contexts = $route_object->getOption('parameters')) &&
      !empty($route_contexts['group']) &&
      ($group = $route_match->getParameter('group'))
    ) {
      $group_membership_request = $this->groupRequestManager->getMembershipRequest($account, $group);
      if (empty($group_membership_request)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}

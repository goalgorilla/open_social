<?php

namespace Drupal\social_group_request\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\Access\GroupMembershipRequestAccessChecker;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Extends the group membership request access checker.
 */
class SocialGroupMembershipRequestAccessChecker extends GroupMembershipRequestAccessChecker {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Check if route requires this checker.
    $requirement = $route->getRequirement('_group_membership_request');

    // Don't interfere if no requirement was specified.
    if ($requirement !== 'TRUE') {
      return AccessResult::neutral();
    }

    if (($group = $route_match->getParameter('group')) instanceof GroupInterface) {
      $group_membership_request = $this->groupRequestManager->getMembershipRequest($account, $group);
      
      // If there's no request, allow access
      if (empty($group_membership_request)) {
        return AccessResult::allowed();
      }

      // Get the request status if available
      $status = $group_membership_request->get(GroupMembershipRequest::STATUS_FIELD)->value;
      // Allow new request if previous one was rejected
      if ($status === GroupMembershipRequest::REQUEST_REJECTED) {
        return AccessResult::allowed();
      }

      // If there's an active/pending request, forbid access
      return AccessResult::forbidden();
    }

    return AccessResult::forbidden();
  }

} 
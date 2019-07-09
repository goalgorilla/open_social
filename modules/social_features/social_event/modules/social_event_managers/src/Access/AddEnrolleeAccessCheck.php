<?php

namespace Drupal\social_event_managers\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\social_event_managers\SocialEventManagersAccessHelper;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on manage everything enrollments.
 */
class AddEnrolleeAccessCheck implements AccessInterface {
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
    $permission = $route->getRequirement('_enrollee_permission');

    // Don't interfere if no permission was specified.
    // Or it's not the permission we are looking for.
    if ($permission === NULL || $permission !== 'manage everything enrollments') {
      return AccessResult::neutral();
    }

    // Don't interfere if no group was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('node')) {
      return AccessResult::neutral();
    }
    // Don't interfere if the group isn't a real group.
    $node = $parameters->get('node');
    if (!is_null($node) && (!$node instanceof Node)) {
      $node = Node::load($node);
    }

    if (!$node instanceof NodeInterface) {
      return AccessResult::neutral();
    }

    // A user with this access can definitely do everything.
    if ($account->hasPermission('manage everything enrollments')) {
      return AccessResult::allowed();
    }
    $type = $node->getType();
    // Don't interfere if it's not an event.
    if ($type !== 'event') {
      return AccessResult::neutral();
    }
    // AN Users aren't allowed anything.
    if (!$account->isAuthenticated()) {
      return AccessResult::forbidden();
    }

    // Lets return the correct access for our Event Manager.
    $managers_access = SocialEventManagersAccessHelper::getEntityAccessResult($node, 'update', $account);
    if ($managers_access instanceof AccessResultAllowed || $managers_access instanceof AccessResultForbidden) {
      return $managers_access->addCacheableDependency($node);
    }
    // We allow it but lets add the group as dependency to the cache
    // now because field value might be edited and cache should
    // clear accordingly.
    return AccessResult::neutral();
  }
}
<?php

declare(strict_types=1);

namespace Drupal\social_group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\JoinManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides access control for routes based on group settings and policies.
 *
 * Implements access logic to determine whether a user can access a specific
 * route depending on the requirements defined for that route and the group
 * constraints.
 */
class RouteAccess implements AccessInterface {

  /**
   * The join manager.
   */
  protected JoinManagerInterface $joinManager;

  /**
   * RouteAccess constructor.
   *
   * @param \Drupal\social_group\JoinManagerInterface $join_manager
   *   The join manager service.
   */
  public function __construct(JoinManagerInterface $join_manager) {
    $this->joinManager = $join_manager;
  }

  /**
   * Determines access to a route based on group settings and requirements.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route being accessed.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The routeMatch object for the current request.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account of the user attempting to access the route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    if (!$route->getRequirement('_social_group_access')) {
      return AccessResult::allowed();
    }

    $group = $route_match->getParameter('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::allowed();
    }

    $join_method = $this->getJoinMethod($group);

    // The join method is required for further access check.
    // If it's empty, we won't do anything.
    if (!$join_method) {
      return AccessResult::allowed();
    }

    // A member shouldn't be able to join a group if a group has "request"
    // or added" joining methods.
    if ($join_method !== 'direct' && $route_match->getRouteName() === 'entity.group.join') {
      return AccessResult::forbidden('This route is disabled for groups with "request" or added" join methods.');
    }

    return AccessResult::allowed();
  }

  /**
   * Determines the join method for a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity for which to determine the join method.
   *
   * @return string|null
   *   The join method for the group if available, or NULL if no join method
   *   is found.
   */
  protected function getJoinMethod(GroupInterface $group): ?string {
    // Try to get the join method from the group field.
    $join_method = $group->hasField('field_group_allowed_join_method')
      ? $group->get('field_group_allowed_join_method')->value
      : NULL;

    if ($join_method) {
      return $join_method;
    }

    // If the group doesn't have a join method in the appropriate field, try
    // to find a fallback join method definition by join manager.
    $join_definitions = $this->joinManager->relations();
    if (empty($join_definitions)) {
      return NULL;
    }

    foreach ($join_definitions as $definition) {
      // We need all of these three data sets.
      if (!isset($definition['entity_type'], $definition['bundle'], $definition['method'])) {
        continue;
      }

      // Should be a group type.
      if ($definition['entity_type'] !== 'group') {
        continue;
      }
      // And with the appropriate bundle.
      if (!in_array($group->bundle(), (array) $definition['bundle'])) {
        continue;
      }

      return $definition['method'];
    }

    return NULL;
  }

}

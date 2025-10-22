<?php

namespace Drupal\social_course\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provide access check for enrolment.
 */
class EnrollAccessCheck implements AccessInterface {

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
    $group = $route_match->getParameter('group');
    $field = 'field_course_opening_status';
    $opening_date_field = 'field_course_opening_date';

    if ($group->hasField($opening_date_field) && $group->get($opening_date_field)->isEmpty()) {
      return AccessResult::allowed();
    }

    if ($group->hasField($field) && !$group->get($field)->isEmpty() && $group->get($field)->value) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}

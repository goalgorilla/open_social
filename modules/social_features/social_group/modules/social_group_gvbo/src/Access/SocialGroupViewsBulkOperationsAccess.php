<?php

namespace Drupal\social_group_gvbo\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Access\ViewsBulkOperationsAccess;
use Symfony\Component\Routing\Route;

/**
 * Defines VBO module access rules.
 */
class SocialGroupViewsBulkOperationsAccess extends ViewsBulkOperationsAccess {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, RouteMatch $routeMatch): AccessResult {
    $parameters = [
      'view_id' => 'group_manage_members',
      'display_id' => 'page_group_manage_members',
    ];

    $route = $routeMatch->getRouteObject();
    if (!$route instanceof Route) {
      // Deny access if the route object is not available.
      return AccessResult::forbidden();
    }

    foreach ($parameters as $key => $value) {
      $route->setDefault($key, $value);
    }

    $parameters = $parameters + $routeMatch->getParameters()->all();

    $routeMatch = new RouteMatch((string) $routeMatch->getRouteName(), $route, $parameters, $parameters);

    return parent::access($account, $routeMatch);
  }

}

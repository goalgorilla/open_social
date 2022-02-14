<?php

namespace Drupal\social_group_invite\Access;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Access\ViewsBulkOperationsAccess;

/**
 * Defines VBO module access rules.
 */
class SocialGroupInviteVBOAccess extends ViewsBulkOperationsAccess {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, RouteMatch $routeMatch): AccessResultInterface {
    $parameters = [
      'view_id' => 'social_group_invitations',
      'display_id' => 'page_1',
    ];

    $route = $routeMatch->getRouteObject();

    if (!($route instanceof RouteMatchInterface)) {
      return parent::access($account, $routeMatch);
    }

    foreach ($parameters as $key => $value) {
      $route->setDefault($key, $value);
    }

    $parameters = $parameters + $routeMatch->getParameters()->all();

    $routeMatch = new RouteMatch((string) $routeMatch->getRouteName(), $route, $parameters, $parameters);

    return parent::access($account, $routeMatch);
  }

}

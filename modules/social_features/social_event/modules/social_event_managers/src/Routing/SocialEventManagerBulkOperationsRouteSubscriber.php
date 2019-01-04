<?php

namespace Drupal\social_event_managers\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add argument for sending group ID to group permission functionality.
 */
class SocialEventManagerBulkOperationsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route_names = [
      'views_bulk_operations.confirm',
      'views_bulk_operations.execute_configurable',
      'views_bulk_operations.update_selection',
      'social_event_managers.vbo.execute_configurable',
    ];

//    foreach ($route_names as $route_name) {
//      $route = $collection->get($route_name);
//      $route->setDefault('node', NULL);
//    }
  }

}

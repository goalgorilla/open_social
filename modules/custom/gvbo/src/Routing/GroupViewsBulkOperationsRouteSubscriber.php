<?php

namespace Drupal\gvbo\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add argument for sending group ID to group permission functionality.
 */
class GroupViewsBulkOperationsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route_names = [
      'views_bulk_operations.confirm',
      'views_bulk_operations.execute_configurable',
      'views_bulk_operations.update_selection',
    ];

    foreach ($route_names as $route_name) {
      $route = $collection->get($route_name);
      $route->setPath($route->getPath() . '/{group}');
      $route->setDefault('group', NULL);

      if ($route_name === 'views_bulk_operations.execute_configurable') {
        $route->setDefault('_form', '\Drupal\gvbo\Form\GroupViewsBulkOperationsConfigureAction');
      }
    }
  }

}

<?php

namespace Drupal\social_content_block\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_content_block\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route_names = [
      'block_content.add_form',
      'entity.block_content.canonical',
    ];

    foreach ($route_names as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setOption('_admin_route', FALSE);
      }
    }
  }

}

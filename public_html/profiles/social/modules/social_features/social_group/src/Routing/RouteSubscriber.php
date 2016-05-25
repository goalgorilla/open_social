<?php

/**
 * @file
 * Contains \Drupal\social_group\Routing\RouteSubscriber.
 */

namespace Drupal\social_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_group\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Route the group view page to group/{group}/timeline
    if ($route = $collection->get('entity.group.canonical')) {
      $route->setPath('/group/{group}/stream');
    }
  }
}

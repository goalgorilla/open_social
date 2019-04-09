<?php

namespace Drupal\social_group_default_route\RouteSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_default_route\RouteSubscriber
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) :void {
    // Route the group view page to group/{group}/timeline.
    if ($route = $collection->get('entity.group.canonical')) {
      $route->setPath('/group/{group}/home');
    }
  }

}

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
 * @package Drupal\social_user\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Route the group members page to the group/{group}/membership
    if ($route = $collection->get('entity.group_content.group_membership.collection')) {
      $route->setPath('/group/{group}/membership');
    }
  }

}

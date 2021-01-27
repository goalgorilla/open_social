<?php

namespace Drupal\social_auth_extra\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Remove cache for user/register. so AN doesn't have social issues.
 */
class RouteSubscriber extends RouteSubscriberBase  {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route_register = $collection->get('user.register')) {
      $route_register->setOption('no_cache', TRUE);
    }
  }

}

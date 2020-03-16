<?php

namespace Drupal\social_group_request\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class SocialGroupRequestRouteSubscriber.
 */
class SocialGroupRequestRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('grequest.request_membership')) {
      $route->setDefaults([
        '_controller' => '\Drupal\social_group_request\Controller\GroupRequestController::requestMembership',
      ]);
    }
  }

}

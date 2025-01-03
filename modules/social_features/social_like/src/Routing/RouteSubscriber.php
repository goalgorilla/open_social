<?php

namespace Drupal\social_like\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {

    if ($route = $collection->get('like_and_dislike.vote')) {
      $route->setRequirement('_custom_access', 'Drupal\social_like\Access\VoteAccess::access');
    }
  }

}

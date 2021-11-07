<?php

namespace Drupal\social_event\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * @package Drupal\social_event\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {

    if ($route = $collection->get('view.events.events_overview')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = "\Drupal\social_event\Controller\SocialEventController::myEventAccess";
      $route->setRequirements($requirements);
    }

  }

}

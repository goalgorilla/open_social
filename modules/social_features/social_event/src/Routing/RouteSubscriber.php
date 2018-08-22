<?php

namespace Drupal\social_event\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_event\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    if ($route = $collection->get('view.events.events_overview')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = "\Drupal\social_event\Controller\SocialEventController::myEventAccess";
      $route->setRequirements($requirements);
    }

  }

}

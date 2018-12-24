<?php

namespace Drupal\social_event_an_enroll\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('social_event.views_bulk_operations.confirm')) {
      $route->setDefault('_form', '\Drupal\social_event_an_enroll\Form\EventAnEnrollConfirmActionForm');
    }
  }

}

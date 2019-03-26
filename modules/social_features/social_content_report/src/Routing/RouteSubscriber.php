<?php

namespace Drupal\social_content_report\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_content_report\Access\FlagAccessCheck;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Note that the second parameter of setRequirement() is a string.
    if ($route = $collection->get('flag.field_entry')) {
      $route->setRequirement('_custom_access', FlagAccessCheck::class . '::access');
    }
  }

}

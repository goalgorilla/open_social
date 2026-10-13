<?php

declare(strict_types=1);

namespace Drupal\social_event_an_enroll\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters routes of anonymous event enrollment pages.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('view.manage_enrollments.page')) {
      $route->setRequirement('_social_event_external_registration_access', 'TRUE');
    }
  }

}

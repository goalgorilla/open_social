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

    // Alter access checkers for enrollments view pages.
    $routes = [
      'view.event_manage_enrollments.page_manage_enrollments',
      'view.event_enrollments.view_enrollments',
      'view.event_manage_enrollment_invites.page_manage_enrollment_invites',
    ];
    foreach ($routes as $name) {
      if ($route = $collection->get($name)) {
        $requirements = $route->getRequirements();
        $requirements['_social_event_enrollments_access'] = 'TRUE';
        $route->setRequirements($requirements);
      }
    }

    // Native enrolment views stay closed when registration is external.
    $external_registration_routes = [
      'view.event_enrollments.view_enrollments',
      'view.event_manage_enrollment_requests.page_manage_enrollment_requests',
    ];
    foreach ($external_registration_routes as $name) {
      if ($route = $collection->get($name)) {
        $route->setRequirement('_social_event_external_registration_access', 'TRUE');
      }
    }
  }

}

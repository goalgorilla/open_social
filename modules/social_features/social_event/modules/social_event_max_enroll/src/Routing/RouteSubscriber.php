<?php

namespace Drupal\social_event_max_enroll\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber listens to the dynamic route events.
 *
 * We need it to alter social_event_invite controller where we need to prevent
 * users accept the invite if an event has no spots left.
 *
 * @package Drupal\social_event_max_enroll\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('social_event_invite.update_enrollment_invite')) {
      $route->setDefaults([
        '_controller' => '\Drupal\social_event_max_enroll\Controller\UserEnrollInviteControllerAlter::updateEnrollmentInvite',
      ]);
    }
  }

}

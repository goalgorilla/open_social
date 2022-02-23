<?php

namespace Drupal\social_event_invite\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_event_invite\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('social_event_invite.invite_user')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = 'social_event_invite.access::eventFeatureAccess';
      $route->setRequirements($requirements);
    }

    if ($route = $collection->get('view.event_manage_enrollment_invites.page_manage_enrollment_invites')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = 'social_event_invite.access::eventFeatureAccess';
      $route->setRequirements($requirements);
    }

    if ($route = $collection->get('view.user_event_invites.page_user_event_invites')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = 'social_event_invite.access::userInviteAccess';
      $route->setRequirements($requirements);
    }
  }

}

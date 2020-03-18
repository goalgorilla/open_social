<?php

namespace Drupal\social_event\Routing;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_event\Routing
 */
class EnrollRequestsOverviewSubscriber implements EventSubscriberInterface {

  /**
   * Get the request events.
   *
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkAccessToEnrollRequestsOverview'];
    return $events;
  }

  /**
   * Check if the user is allowed to view this overview.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function checkAccessToEnrollRequestsOverview(GetResponseEvent $event) {
    $current_route = \Drupal::routeMatch()->getRouteName();
    // First, lets check if the route matches.
    if ($current_route === 'view.event_manage_enrollment_requests.page_manage_enrollment_requests') {
      // Now lets get some stuff we need to perform some checks on.
      $current_event = social_event_get_current_event();
      $current_user = \Drupal::currentUser();

      // Get the event owner/author and it's organisers (if any).
      $event_accountables['owner'] = $current_event->getOwnerId();
      // Also check whether the social_event_managers is enabled so we can
      // check if the user might be an organiser/manager of this event.
      if (\Drupal::moduleHandler()->moduleExists('social_event_managers')) {
        $event_accountables['organiser'] = social_event_manager_or_organizer($current_event);
      }

      // Now, lets check:
      // - If the current user has a permission to see the overview.
      // - If the current user is the owner/creator of this event.
      // - If the current user is an organiser/manager of this event.
      // And then allow access.
      if ($current_user->hasPermission('manage event enrollment requests')
        || $event_accountables['owner'] === $current_user->id()
        || $event_accountables['organiser'] === TRUE) {
        return;
      }

      // We deny the rest and send them straight to where they came from!
      $requestHeaders = $event->getRequest()->server->getHeaders();
      $referer = $requestHeaders['REFERER'];
      $event->setResponse(new RedirectResponse(Url::fromUri($referer)));
    }
  }

}

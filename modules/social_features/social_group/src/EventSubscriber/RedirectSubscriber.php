<?php

namespace Drupal\social_group\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * Get the request events.
   *
   * @return mixed
   *   Returns request events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function checkForRedirection(RequestEvent $event) {
    // Check if there is a group object on the current route.
    if (($group = _social_group_get_current_group()) === NULL) {
      return;
    }

    // Get the current route name for the checks being performed below.
    $route_name = \Drupal::routeMatch()->getRouteName();

    // Redirect the group content collection index to the group canonical URL.
    if ($route_name === 'entity.group_content.collection') {
      $event->setResponse(new RedirectResponse(Url::fromRoute('entity.group.canonical', ['group' => $group->id()])
        ->toString()));
    }
    elseif ($route_name === 'view.group_pending_members.page_1') {
      // We have two pages with a list of group members. One of them is provided
      // by the grequest module and is not correct. So we add a redirect to the
      // custom one.
      $event->setResponse(new RedirectResponse(Url::fromRoute('view.group_membership_requests.pending', [
        'group' => $group->id(),
      ])->toString()));
    }
  }

}

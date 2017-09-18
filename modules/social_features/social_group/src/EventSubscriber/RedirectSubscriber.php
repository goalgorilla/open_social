<?php

namespace Drupal\social_group\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }

  /**
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   *    The event.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    // Check if there is a group object on the current route.
    $group = _social_group_get_current_group();
    // Get the current route name for the checks being performed below.
    $route_name = \Drupal::routeMatch()->getRouteName();
    // Get the current user.
    $user = \Drupal::currentUser();
    // The array of forbidden routes.
    $routes = [
      'entity.group.canonical',
      'entity.group.join',
      'view.group_events.page_group_events',
      'view.group_topics.page_group_topics',
    ];
    // If a group is set, the bundle is closed_group and user does not have
    // permission to manage all groups.
    if ($group && $group->bundle() == 'closed_group' && !$user->hasPermission('manage all groups')) {
      // If the user is not an member of this group.
      if (!$group->getMember($user) && in_array($route_name, $routes)) {
        $request = $event->getRequest();
        $url = Url::fromRoute('view.group_information.page_group_about', [
          'group' => $group->id(),
        ]);
        // To pretend cyclic redirection in cases when the About page is a canonical
        // page, throw the "access denied" exception.
        if ($url->toString() === $request->getRequestUri()) {
          throw new AccessDeniedHttpException();
        }
        else {
          $response = new RedirectResponse($url->toString());
          $event->setResponse($response);
        }
      }
    }
  }
}

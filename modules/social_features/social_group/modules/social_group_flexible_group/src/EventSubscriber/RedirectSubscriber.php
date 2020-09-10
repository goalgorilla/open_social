<?php

namespace Drupal\social_group_flexible_group\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_flexible_group\EventSubscriber
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
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    // Check if there is a group object on the current route.
    $group = _social_group_get_current_group();
    // Get the current route name for the checks being performed below.
    $routeMatch = \Drupal::routeMatch()->getRouteName();
    // Get the current user.
    $user = \Drupal::currentUser();
    // The array of forbidden routes.
    $routes = [
      'entity.group.canonical',
      'view.group_events.page_group_events',
      'view.group_topics.page_group_topics',
      'social_group.stream',
    ];

    // If a group is set, and the type is flexible_group.
    if ($group && $group->getGroupType()->id() === 'flexible_group') {
      if ($user->hasPermission('manage all groups')) {
        return;
      }
      // If the user is not a member and if "Allowed join method" is not set to
      // "Join directly" in this group.
      elseif (
        !$group->getMember($user) &&
        $routeMatch === 'entity.group.join' &&
        !social_group_flexible_group_can_join_directly($group)
      ) {
        $event->setResponse(new RedirectResponse(Url::fromRoute('view.group_information.page_group_about', ['group' => $group->id()])
          ->toString()));
      }
      elseif (
        !$group->getMember($user) && in_array($routeMatch, $routes, FALSE) &&
        !social_group_flexible_group_community_enabled($group) &&
        !social_group_flexible_group_public_enabled($group)
      ) {
        $event->setResponse(new RedirectResponse(Url::fromRoute('view.group_information.page_group_about', ['group' => $group->id()])
          ->toString()));
      }
    }
  }

}

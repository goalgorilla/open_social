<?php

namespace Drupal\social_group_flexible_group\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
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
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Redirectsubscriber construct.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   */
  public function __construct(CurrentRouteMatch $route_match, AccountProxy $current_user) {
    $this->currentRoute = $route_match;
    $this->currentUser = $current_user;
  }

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
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = _social_group_get_current_group();
    // Get the current route name for the checks being performed below.
    $routeMatch = $this->currentRoute->getRouteName();
    // The array of forbidden routes.
    $routes = [
      'entity.group.canonical',
      'entity.group.join',
      'view.group_events.page_group_events',
      'view.group_topics.page_group_topics',
      'social_group.stream',
    ];

    // If a group is set, and the type is flexible_group.
    if ($group instanceof GroupInterface && $group->getGroupType()->id() === 'flexible_group') {
      if ($this->currentUser->hasPermission('manage all groups')) {
        return;
      }
      // If the user is not an member of this group.
      elseif (!$group->getMember($this->currentUser) && in_array($routeMatch, $routes, FALSE)
        && !social_group_flexible_group_community_enabled($group)
        && !social_group_flexible_group_public_enabled($group)) {
        $event->setResponse(new RedirectResponse(Url::fromRoute('view.group_information.page_group_about', ['group' => $group->id()])
          ->toString()));
      }
    }
  }

}

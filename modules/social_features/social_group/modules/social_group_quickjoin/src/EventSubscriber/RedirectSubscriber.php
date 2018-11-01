<?php

namespace Drupal\social_group_quickjoin_quickjoin\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\group\Entity\Group;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_quickjoin\EventSubscriber
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
    $events[KernelEvents::REQUEST][] = ['groupQuickJoin'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function groupQuickJoin(GetResponseEvent $event) {

    // First check if the current route is the group canonical.
    $routeMatch = $this->currentRoute->getRouteName();
    // Not group canonical, then we leave.
    if ($routeMatch != 'entity.group.canonical') {
      return;
    }

    // Fetch the group parameter and check if's an actual group.
    $group = $this->currentRoute->getParameter('group');
    // Not group, then we leave.
    if (!$group instanceof Group) {
      return;
    }

    return;

//    $event->setResponse(new RedirectResponse(Url::fromRoute($route, ['group' => $group->id()])->toString()));
  }

}

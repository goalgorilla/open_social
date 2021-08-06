<?php

namespace Drupal\social_group_default_route\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_default_route\EventSubscriber
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
    $events[KernelEvents::REQUEST][] = ['groupLandingPage'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function groupLandingPage(GetResponseEvent $event) {

    // First check if the current route is the group canonical.
    $routeMatch = $this->currentRoute->getRouteName();

    // Not group canonical, then we leave.
    if (
      $routeMatch !== 'entity.group.canonical' &&
      $routeMatch !== 'social_group_default.group_home'
    ) {
      return;
    }

    // Fetch the group parameter and check if's an actual group.
    $group = $this->currentRoute->getParameter('group');
    // Not group, then we leave.
    if (!$group instanceof Group) {
      return;
    }

    // Set the already default redirect route.
    $defaultRoute = 'social_group.stream';
    $defaultClosedRoute = 'view.group_information.page_group_about';

    // Check if this group has a custom route set.
    $route = $group->getFieldValue('default_route', 'value');

    // Check if current user is a member.
    if ($group->getMember(User::load($this->currentUser->id())) === FALSE) {
      $route = $group->getFieldValue('default_route_an', 'value');
      // If you're not a member and the group type is closed.
      if ($route === NULL) {
        $route = ($group->getGroupType()->id() === 'closed_group') ? $defaultClosedRoute : $defaultRoute;
      }
    }

    // Still no route here? Then we use the normal default.
    if ($route === NULL) {
      $route = $defaultRoute;
    }

    // Determine the URL we want to redirect to.
    $url = Url::fromRoute($route, ['group' => $group->id()]);

    // If it's not set, set to canonical, or the current user has no access.
    if (!isset($route) || ($route === $routeMatch) || $url->access($this->currentUser) === FALSE) {
      // This basically means that the normal flow remains intact.
      return;
    }
    // Redirect.
    $event->setResponse(new RedirectResponse($url->toString()));
  }

}

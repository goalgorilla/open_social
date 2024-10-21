<?php

namespace Drupal\social_group_default_route\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\social_group\SocialGroupInterface;
use Drupal\social_group_default_route\RedirectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
   * The redirect service.
   *
   * @var \Drupal\social_group_default_route\RedirectService
   */
  protected RedirectService $redirectService;

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\social_group_default_route\RedirectService $redirect_service
   *   The redirect service.
   */
  public function __construct(CurrentRouteMatch $route_match, AccountProxy $current_user, RedirectService $redirect_service) {
    $this->currentRoute = $route_match;
    $this->currentUser = $current_user;
    $this->redirectService = $redirect_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['groupLandingPage'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function groupLandingPage(RequestEvent $event) {
    // First check if the current route is the group canonical.
    $route_name = $this->currentRoute->getRouteName();

    // Not group canonical, then we leave.
    if (
      $route_name !== $this->redirectService::DEFAULT_GROUP_ROUTE &&
      $route_name !== $this->redirectService::ALTERNATIVE_ROUTE
    ) {
      return;
    }

    // Fetch the group parameter and check if's an actual group.
    $group = $this->currentRoute->getParameter('group');

    // Not group, then we leave.
    if (!$group instanceof SocialGroupInterface) {
      return;
    }

    $this->redirectService->doRedirect($event, $group);
  }

}

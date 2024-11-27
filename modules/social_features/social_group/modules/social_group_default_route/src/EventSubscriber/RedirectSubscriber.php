<?php

namespace Drupal\social_group_default_route\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_group\SocialGroupInterface;
use Drupal\social_group_default_route\SocialGroupDefaultRouteRedirectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_default_route\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * SocialGroupDefaultRouteRedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRoute
   *   The current route.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\social_group_default_route\SocialGroupDefaultRouteRedirectService $redirectService
   *   The redirect service.
   */
  public function __construct(
    protected RouteMatchInterface $currentRoute,
    protected AccountProxyInterface $currentUser,
    protected SocialGroupDefaultRouteRedirectService $redirectService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onKernelException', 101];
    $events[KernelEvents::REQUEST][] = ['groupLandingPage'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function groupLandingPage(RequestEvent $event): void {
    // First check if the current route is the group canonical.
    $route_name = $this->currentRoute->getRouteName();

    // Not group canonical, then we leave.
    if (
      $route_name !== $this->redirectService::DEFAULT_GROUP_ROUTE &&
      $route_name !== $this->redirectService::ALTERNATIVE_ROUTE
    ) {
      return;
    }

    $group = $this->redirectService->getGroup();

    // Not group, then we leave.
    if (!$group instanceof SocialGroupInterface) {
      return;
    }
    // If redirection isn't applicable for current group bundle.
    $group_routes = $this->redirectService->getGroupDefaultRoutes($group);
    if (empty($group_routes)) {
      return;
    }

    $this->redirectService->doRedirect($event, $group);
  }

  /**
   * Redirect on kernel exception.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event object.
   */
  public function onKernelException(ExceptionEvent $event): void {
    $exception = $event->getThrowable();

    $group = $this->redirectService->getGroup();
    // Not group, then we leave.
    if (!$group instanceof SocialGroupInterface) {
      return;
    }
    // If redirection isn't applicable for current group bundle.
    $group_routes = $this->redirectService->getGroupDefaultRoutes($group);
    if (empty($group_routes)) {
      return;
    }

    if ($exception instanceof AccessDeniedHttpException) {
      $this->redirectService->doRedirect($event, $group);
    }
  }

}

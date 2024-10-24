<?php

namespace Drupal\social_group_flexible_group\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_group\SocialGroupInterface;
use Drupal\social_group_default_route\SocialGroupDefaultRouteRedirectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_flexible_group\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current active user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The currently active route match object.
   * @param \Drupal\social_group_default_route\SocialGroupDefaultRouteRedirectService $redirectService
   *   The redirect service.
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected RouteMatchInterface $routeMatch,
    protected SocialGroupDefaultRouteRedirectService $redirectService,
  ) {
  }

  /**
   * Get the request events.
   *
   * @return mixed
   *   Returns request events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onKernelException', 100];
    return $events;
  }

  /**
   * Redirect on exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The exception event.
   */
  public function onKernelException(ExceptionEvent $event): void {
    // Check if there is a group object on the current route.
    $group = $this->redirectService->getGroup();

    if ($group && $group->bundle() === 'flexible_group') {
      $this->redirectOnAccessDeniedException($group, $event);
    }
  }

  /**
   * Redirect on access denied exception.
   *
   * @param \Drupal\social_group\SocialGroupInterface $group
   *   The group object.
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The exception event.
   */
  protected function redirectOnAccessDeniedException(SocialGroupInterface $group, ExceptionEvent $event): void {
    $exception = $event->getThrowable();

    // Do not redirect form access denied if user doesn't have access to
    // view the group (secret group, etc.).
    if (!$group->access('view', $this->currentUser) ||
      !$exception instanceof AccessDeniedHttpException) {
      return;
    }

    $this->redirectService->doRedirect($event, $group);
  }

}

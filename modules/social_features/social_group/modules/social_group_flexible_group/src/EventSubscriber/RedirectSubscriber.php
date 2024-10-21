<?php

namespace Drupal\social_group_flexible_group\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\Group;
use Drupal\social_group\SocialGroupInterface;
use Drupal\social_group_default_route\RedirectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_flexible_group\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The redirect service.
   *
   * @var \Drupal\social_group_default_route\RedirectService
   */
  protected RedirectService $redirectService;

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\social_group_default_route\RedirectService $redirect_service
   *   The redirect service.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    RouteMatchInterface $route_match,
    RedirectService $redirect_service
  ) {
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->redirectService = $redirect_service;
  }

  /**
   * Get the request events.
   *
   * @return mixed
   *   Returns request events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    $events[KernelEvents::EXCEPTION][] = ['onKernelException', 100];
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
    if (!($group = _social_group_get_current_group())) {
      return;
    }

    // If a group type is flexible group.
    if ($group->bundle() !== 'flexible_group') {
      return;
    }

    // If the user can manage groups or the user is a member.
    if (
      $this->currentUser->hasPermission('manage all groups') ||
      $group->hasMember($this->currentUser)
    ) {
      return;
    }

    // Get the current route name for the checks being performed below.
    $route_name = $this->routeMatch->getRouteName();

    // The array of forbidden routes.
    $routes = [
      'entity.group.canonical',
      'view.group_events.page_group_events',
      'view.group_topics.page_group_topics',
      'view.group_books.page_group_books',
      'social_group.stream',
    ];

    // If "Allowed join method" is not set to "Join directly" in this group.
    if (
      $route_name === 'entity.group.join' &&
      !social_group_flexible_group_can_join_directly($group)
    ) {
      $this->redirectService->doRedirect($event, $group);
    }
    elseif (
      in_array($route_name, $routes) &&
      !social_group_flexible_group_community_enabled($group) &&
      !social_group_flexible_group_public_enabled($group)
    ) {
      $this->redirectService->doRedirect($event, $group);
    }
  }

  /**
   * Redirect on exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The exception event.
   */
  public function onKernelException(ExceptionEvent $event): void {
    $route_name = $this->routeMatch->getRouteName();
    // Do not redirect from group content pages.
    if ($route_name && preg_match('/^entity\.group_content\..*/', $route_name)) {
      return;
    }
    $exception = $event->getThrowable();

    if ($exception instanceof AccessDeniedHttpException) {
      // Check if there is a group object on the current route.
      $group = $this->routeMatch->getParameter('group');
      // On some routes group param could be string.
      if (is_string($group)) {
        $group = Group::load($group);
      }

      if (!$group instanceof SocialGroupInterface) {
        return;
      }

      // If a group type is flexible group.
      if ($group->bundle() !== 'flexible_group') {
        return;
      }
      // Do not redirect form access denied if user doesn't have access to
      // view the group (secret group, etc.).
      if (!$group->access('view', $this->currentUser)) {
        return;
      }

      $this->redirectService->doRedirect($event, $group);
    }

  }

}

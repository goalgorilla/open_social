<?php

namespace Drupal\social_group_flexible_group\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    RouteMatchInterface $route_match
  ) {
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
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
      $group->getMember($this->currentUser)
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
      'social_group.stream',
    ];

    // If "Allowed join method" is not set to "Join directly" in this group.
    if (
      $route_name === 'entity.group.join' &&
      !social_group_flexible_group_can_join_directly($group)
    ) {
      $this->doRedirect($event, $group);
    }
    elseif (
      in_array($route_name, $routes) &&
      !social_group_flexible_group_community_enabled($group) &&
      !social_group_flexible_group_public_enabled($group)
    ) {
      $this->doRedirect($event, $group);
    }
  }

  /**
   * Makes redirect to the "About" group tab.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   */
  protected function doRedirect(GetResponseEvent $event, GroupInterface $group) {
    $url = Url::fromRoute('view.group_information.page_group_about', [
      'group' => $group->id(),
    ]);

    $event->setResponse(new RedirectResponse($url->toString()));
  }

}

<?php

namespace Drupal\social_group\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * Retrieves the currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs redirect subscriber oject.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The currently active route match object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   */
  public function __construct(RouteMatchInterface $current_route_match, AccountProxyInterface $current_user) {
    $this->routeMatch = $current_route_match;
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
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function checkForRedirection(RequestEvent $event) {
    // Check if there is a group object on the current route.
    $group = _social_group_get_current_group();

    // Get the current route name for the checks being performed below.
    $routeMatch = $this->routeMatch->getRouteName();

    // Redirect the group content collection index to the group canonical URL.
    if ($routeMatch === 'entity.group_content.collection') {
      $event->setResponse(new RedirectResponse(Url::fromRoute('entity.group.canonical', ['group' => $group->id()])
        ->toString()));
    }

    // The array of forbidden routes.
    $routes = [
      'entity.group.canonical',
      'entity.group.join',
      'view.group_events.page_group_events',
      'view.group_topics.page_group_topics',
    ];
    // If a group is set, and the type is closed_group.
    if ($group && $group->getGroupType()->id() == 'closed_group') {
      if ($this->currentUser->id() != 1) {
        if ($this->currentUser->hasPermission('manage all groups')) {
          return;
        }
        // If the user is not an member of this group.
        elseif (!$group->getMember($this->currentUser) && in_array($routeMatch, $routes)) {
          $event->setResponse(new RedirectResponse(Url::fromRoute('view.group_information.page_group_about', ['group' => $group->id()])
            ->toString()));
        }
      }
    }
  }

}

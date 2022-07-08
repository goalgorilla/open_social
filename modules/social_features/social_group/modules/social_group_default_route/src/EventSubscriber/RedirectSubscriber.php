<?php

namespace Drupal\social_group_default_route\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\social_group\SocialGroupInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_default_route\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * The route name of the default page of any group type except closed groups.
   */
  private const DEFAULT_ROUTE = 'social_group.stream';

  /**
   * The route name of the group default page is provided by the current module.
   */
  private const ALTERNATIVE_ROUTE = 'social_group_default.group_home';

  /**
   * The route name of the default page of any group.
   */
  private const DEFAULT_GROUP_ROUTE = 'entity.group.canonical';

  /**
   * The route name of the default page of closed groups.
   */
  private const DEFAULT_CLOSED_ROUTE = 'view.group_information.page_group_about';

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
   * RedirectSubscriber constructor.
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
      $route_name !== self::DEFAULT_GROUP_ROUTE &&
      $route_name !== self::ALTERNATIVE_ROUTE
    ) {
      return;
    }

    // Fetch the group parameter and check if's an actual group.
    $group = $this->currentRoute->getParameter('group');

    // Not group, then we leave.
    if (!$group instanceof SocialGroupInterface) {
      return;
    }

    // Check if current user is a member.
    if (!$group->hasMember($this->currentUser)) {
      /** @var string|null $route */
      $route = $group->default_route_an->value;

      if ($route === NULL) {
        $route = $group->getGroupType()->id() === 'closed_group'
          ? self::DEFAULT_CLOSED_ROUTE : self::DEFAULT_ROUTE;
      }
    }
    else {
      /** @var string|null $route */
      $route = $group->default_route->value;

      // Still no route here? Then we use the normal default.
      if ($route === NULL) {
        $route = self::DEFAULT_ROUTE;
      }
    }

    // Determine the URL we want to redirect to.
    $url = Url::fromRoute($route, ['group' => $group->id()]);

    // If it's not set, set to canonical, or the current user has no access.
    if ($route === $route_name || $url->access($this->currentUser) === FALSE) {
      // This basically means that the normal flow remains intact.
      return;
    }

    // Redirect.
    $event->setResponse(new RedirectResponse($url->toString()));
  }

}

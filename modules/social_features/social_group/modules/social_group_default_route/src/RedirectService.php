<?php

namespace Drupal\social_group_default_route;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\social_group\SocialGroupInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class RedirectService.
 */
class RedirectService {

  /**
   * The route name of the default page of any group type except closed groups.
   */
  public const DEFAULT_ROUTE = 'social_group.stream';

  /**
   * The route name of the group default page is provided by the current module.
   */
  public const ALTERNATIVE_ROUTE = 'social_group_default.group_home';

  /**
   * The route name of the default page of any group.
   */
  public const DEFAULT_GROUP_ROUTE = 'entity.group.canonical';

  /**
   * The route name of the default page of closed groups.
   */
  public const DEFAULT_CLOSED_ROUTE = 'view.group_information.page_group_about';

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The current route.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The current user.
   */
  public function __construct(
    protected CurrentRouteMatch $routeMatch,
    protected AccountProxy $currentUser
  ) {
  }

  /**
   * Do redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent|\Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event object.
   * @param \Drupal\social_group\SocialGroupInterface $group
   *   The group object.
   */
  public function doRedirect(ExceptionEvent|RequestEvent $event, SocialGroupInterface $group): void {
    $route_name = $this->routeMatch->getRouteName();
    // Check if current user is a member.
    if (!$group->hasMember($this->currentUser)) {
      /** @var string|null $route */
      $route = $group->default_route_an->value;

      if ($route === NULL) {
        $route = self::DEFAULT_CLOSED_ROUTE;
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

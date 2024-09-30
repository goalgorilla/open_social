<?php

namespace Drupal\social_group\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  const DEFAULT_REDIRECTION_ROUTE = 'view.group_information.page_group_about';

  /**
   * Constructs a new RedirectSubscriber object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected CurrentRouteMatch $currentRouteMatch,
    protected AccountInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
    protected RequestStack $requestStack,
  ) {
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
    if (($group = _social_group_get_current_group()) === NULL) {
      return;
    }

    // Get the current route name for the checks being performed below.
    $route_name = $this->currentRouteMatch->getRouteName();

    $current_path = $this->requestStack->getCurrentRequest()?->getPathInfo();

    // Redirect the group content collection index to the group canonical URL.
    if ($route_name === 'entity.group_content.collection') {
      $event->setResponse(new RedirectResponse(Url::fromRoute('entity.group.canonical', ['group' => $group->id()])
        ->toString()));
    }
    elseif ($route_name === 'view.group_pending_members.page_1') {
      // We have two pages with a list of group members. One of them is provided
      // by the grequest module and is not correct. So we add a redirect to the
      // custom one.
      $event->setResponse(new RedirectResponse(Url::fromRoute('view.group_pending_members.membership_requests', [
        'group' => $group->id(),
      ])->toString()));
    }
    // The group canonical url is '/group/{group}/stream' or
    // '/group/{group}/home', so we need to do redirect on this url if user
    // doesn't have a permission. We can't revoke access to view canonical
    // url of group, so we should do redirect on request event.
    // @see Drupal\social_group\Routing\RouteSubscriber::alterRoutes().
    // @see Drupal\social_group_default_route\RouteSubscriber\RouteSubscriber.
    elseif ($current_path &&
      (str_contains($current_path, '/stream') ||
        str_contains($current_path, '/home')) &&
      !$group->hasPermission('view group stream page', $this->currentUser) &&
      $this->isRedirectApplicable($group) &&
      $group->access('view', $this->currentUser)
    ) {
      $redirection_url = $this->getRedirectionUrl($group);
      if ($redirection_url) {
        $this->doRedirect($event, $redirection_url);
      }
    }
  }

  /**
   * Redirect on exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The exception event.
   */
  public function onKernelException(ExceptionEvent $event): void {
    $exception = $event->getThrowable();
    if ($exception instanceof AccessDeniedHttpException) {
      // Check if there is a group object on the current route.
      $group = $this->currentRouteMatch->getParameter('group');
      // On some routes group param could be string.
      if (is_string($group)) {
        $group = Group::load($group);
      }

      if (!$group instanceof GroupInterface) {
        return;
      }

      if (!$this->isRedirectApplicable($group) ||
        !$group->access('view', $this->currentUser)
      ) {
        return;
      }

      $url = $this->getRedirectionUrl($group);
      if ($url) {
        // Redirect when user doesn't have access to the route.
        $this->doRedirect($event, $url);
      }
    }

  }

  /**
   * Do redirect by conditions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent|\Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event object.
   * @param string $url
   *   The url.
   */
  protected function doRedirect(ExceptionEvent|RequestEvent $event, string $url): void {
    $response = new RedirectResponse($url);
    $event->setResponse($response);
  }

  /**
   * Get redirection url.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return ?string
   *   The url if it exists, otherwise NULL.
   */
  protected function getRedirectionUrl(GroupInterface $group): ?string {
    $redirection_route = $this->configFactory->get('social_group.settings')->get('redirection.redirection_route') ?? self::DEFAULT_REDIRECTION_ROUTE;
    // Default url to 'About' route.
    $default_url = Url::fromRoute(self::DEFAULT_REDIRECTION_ROUTE, ['group' => $group->id()])->toString();
    $current_path = $this->requestStack->getCurrentRequest()?->getPathInfo();
    // In case when Anonymous user will be redirected to inaccessible route we
    // shouldn't redirect it.
    if ($redirection_route === $this->currentRouteMatch->getRouteName() && $this->currentUser->isAnonymous()) {
      return NULL;
    }
    // In case when non-Anonymous user will be redirected to inaccessible route
    // we should redirect it to the "About" route (should be accessible always).
    elseif ($redirection_route === $this->currentRouteMatch->getRouteName() && !$this->currentUser->isAnonymous()) {
      return $default_url;
    }
    // In the case when canonical group url is '/stream' and
    // it is a redirection route we should redirect only when user has access
    // to view group.
    elseif ($current_path &&
      (str_contains($current_path, '/stream')) &&
      !$group->hasPermission('view group stream page', $this->currentUser) &&
      $redirection_route === 'social_group.stream'
    ) {
      return $group->access('view', $this->currentUser) ? $default_url : NULL;
    }
    // In other cases we should apply default url for redirection.
    else {
      return Url::fromRoute($redirection_route, ['group' => $group->id()])->toString();
    }
  }

  /**
   * Is redirect applicable for group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return bool
   *   TRUE if applicable, otherwise FALSE.
   */
  protected function isRedirectApplicable(GroupInterface $group): bool {
    $group_bundles = $this->configFactory->get('social_group.settings')->get('redirection.group_bundles');
    return in_array($group->bundle(), $group_bundles);
  }

}

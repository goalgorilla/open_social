<?php

namespace Drupal\social_user\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds redirect to default user landing page from canonical user and profile.
 *
 * @package Drupal\social_user\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * Redirect subscriber construct.
   */
  public function __construct(
    /**
     * The current route.
     *
     * @var \Drupal\Core\Routing\CurrentRouteMatch
     */
    protected CurrentRouteMatch $currentRoute,
    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountProxy
     */
    protected AccountProxy $currentUser,
    /**
     * The config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory) {
  }

  /**
   * Get the request events.
   *
   * @return array
   *   Returns request events.
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['profileLandingPage'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function profileLandingPage(RequestEvent $event): void {
    // First check if the current route is the user or profile canonical.
    $routeMatch = $this->currentRoute->getRouteName();
    // If neither of them, then we leave.
    // Open Social display user's page at /user/uid/information which is
    // default stream page for a user.
    // Therefore, we are redirecting these canonical URLs:
    // 1. /user/{uid}
    // 2. /profile/{profile_id}
    // to user information page or to the page set by admin/SM at
    // /admin/config/opensocial/user.
    if ($routeMatch !== 'entity.user.canonical' && $routeMatch !== 'entity.profile.canonical') {
      return;
    }

    // Fetch the user parameter and check if's an actual user.
    $user = $this->currentRoute->getParameter('user');
    // Not user, then we leave.
    if (!$user instanceof User) {
      // It may be a profile route.
      $profile = $this->currentRoute->getParameter('profile');
      // Fetch the user entity of this profile.
      $user = $profile->getOwner();
    }

    // Set the already default redirect route.
    $defaultRoute = 'social_user.stream';

    // Fetch the settings.
    $settings = $this->configFactory->get('social_user.settings');

    // Check there is a custom route set.
    if ($this->currentUser->id() !== $user->id()) {
      $route = $settings->get('social_user_profile_landingpage');
    }

    // Still no route here? Then we use the normal default.
    if (!isset($route)) {
      $route = $defaultRoute;
    }

    // Determine the URL we want to redirect to.
    $url = Url::fromRoute($route, ['user' => $user->id()]);

    // If it's not set, set to canonical, or the current user has no access.
    if ($route === $routeMatch || $url->access($this->currentUser) === FALSE) {
      // This basically means that the normal flow remains intact.
      return;
    }
    // Redirect.
    $event->setResponse(new RedirectResponse($url->toString()));
  }

}

<?php

namespace Drupal\social_user\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_user\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {


  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Redirectsubscriber construct.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config object.
   */
  public function __construct(CurrentRouteMatch $route_match, AccountProxy $current_user, ConfigFactoryInterface $config_factory) {
    $this->currentRoute = $route_match;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * Get the request events.
   *
   * @return mixed
   *   Returns request events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['profileLandingPage'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function profileLandingPage(GetResponseEvent $event) {
    // First check if the current route is the group canonical.
    $routeMatch = $this->currentRoute->getRouteName();
    // Not group canonical, then we leave.
    if ($routeMatch !== 'entity.user.canonical') {
      return;
    }

    // Fetch the user parameter and check if's an actual user.
    $user = $this->currentRoute->getParameter('user');
    // Not user, then we leave.
    if (!$user instanceof User) {
      return;
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
    if (!isset($route) || ($route === $routeMatch) || $url->access($this->currentUser) === FALSE) {
      // This basically means that the normal flow remains intact.
      return;
    }
    // Redirect.
    $event->setResponse(new RedirectResponse($url->toString()));
  }

}

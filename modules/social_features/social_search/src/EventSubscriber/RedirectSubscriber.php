<?php

namespace Drupal\social_search\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * This class redirects the search pages with prefilled exposed filters because
 * the bug in drupal.org issue #3085806.
 *
 * @package Drupal\social_search\EventSubscriber
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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Redirectsubscriber construct.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(CurrentRouteMatch $route_match, AccountProxy $current_user, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->currentRoute = $route_match;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Get the request events.
   *
   * @return mixed
   *   Returns request events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirectSearchWithPrefilledExposedFilters'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function redirectSearchWithPrefilledExposedFilters(GetResponseEvent $event) {
    $routeMatch = [
      'view.search_users.page_no_value',
      'view.search_users.page',
    ];
    if (!in_array($this->currentRoute->getRouteName(), $routeMatch)) {
      return;
    }
    if ($this->requestStack->getCurrentRequest() === NULL) {
      return;
    }

    $query = $this->requestStack->getCurrentRequest()->query->all();

    // Workaround for drupal.org issue #3085806.
    if (empty($query) || empty($query['created_op'])) {
      $query = ['created_op' => '<'];
      $parameters = $this->currentRoute->getParameters();
      $redirect_path = $this->currentRoute->getRouteName();
      $options = ['query' => $query];
      $route_parameters = ['keys' => $parameters->get('keys')];
      $redirect = Url::fromRoute($redirect_path, $route_parameters, $options);
      $event->setResponse(new RedirectResponse($redirect->toString()));
    }

  }

}

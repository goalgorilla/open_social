<?php

namespace Drupal\social_group_quickjoin\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\social_group_quickjoin\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Redirectsubscriber construct.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configfactory.
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
    $events[KernelEvents::REQUEST][] = ['groupQuickJoin'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function groupQuickJoin(RequestEvent $event) {

    // First check if the current route is the group canonical.
    $routeMatch = $this->currentRoute->getRouteName();
    // Not group canonical, then we leave.
    if ($routeMatch != 'entity.group.join') {
      return;
    }

    // Fetch the group parameter and check if's an actual group.
    $group = $this->currentRoute->getParameter('group');
    // Not group, then we leave.
    if (!$group instanceof Group) {
      return;
    }

    // Fetch the settings.
    $settings = $this->configFactory->get('social_group_quickjoin.settings');

    // Check if the feature enabled.
    if ($settings->get('social_group_quickjoin_enabled') == FALSE) {
      return;
    }

    // Check if the current group type is enabled.
    if ($settings->get('social_group_quickjoin_' . $group->getGroupType()->id()) == FALSE) {
      return;
    }

    // Create quickjoin URL from route.
    $url = Url::fromRoute('social_group_quickjoin.quickjoin_group', [
      'group' => $group->id(),
    ])->toString();

    // Redirect to quickjoin.
    $event->setResponse(new RedirectResponse($url));
  }

}

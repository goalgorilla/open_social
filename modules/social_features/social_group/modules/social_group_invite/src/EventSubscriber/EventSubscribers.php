<?php

namespace Drupal\social_group_invite\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CustomRedirects.
 *
 * @package Drupal\social_group_invite\EventSubscriber
 */
class EventSubscribers implements EventSubscriberInterface {

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
   * CustomRedirects construct.
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
   * Get the request events.
   *
   * @return mixed
   *   Returns request events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['customRedirect'];
    $events[ConfigEvents::SAVE][] = ['checkForInvite'];
    return $events;
  }

  /**
   * Checks for group invite save.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event when config is saved.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function checkForInvite(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig()->getRawData();
    // When group_invitation is enabled, add some default config.
    if (!empty($saved_config['group_type']) &&
      !empty($saved_config['content_plugin']) &&
      $saved_config['content_plugin'] === 'group_invitation') {
      // Load the Group type and add permissions.
      $group_type = GroupType::load($saved_config['group_type']);
      if ($group_type instanceof GroupTypeInterface) {
        social_group_invite_set_default_permissions_for_group_type($group_type);
      }
    }
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function customRedirect(RequestEvent $event) {
    // First check if the current route is the group canonical.
    $routeMatch = $this->currentRoute->getRouteName();

    $routes_to_check = [
      'view.group_invitations.page_1',
      'view.my_invitations.page_1',
    ];

    // Not related to group invite, we leave.
    if (!in_array($routeMatch, $routes_to_check, TRUE)) {
      return;
    }

    $url = NULL;
    // For the user group invite overview, we need the current user
    // to be a LU in order to be able to build the URL.
    if ($routeMatch === 'view.my_invitations.page_1') {
      // Check if user is logged in.
      if ($this->currentUser->isAnonymous()) {
        return;
      }
      // Determine the URL we want to redirect to.
      $url = Url::fromRoute('view.social_group_user_invitations.page_1', ['user' => $this->currentUser->id()]);
    }

    // For the group invites overview, we need the group
    // in order to be able to build the URL.
    if ($routeMatch === 'view.group_invitations.page_1') {
      // Fetch the group parameter and check if's an actual group.
      $group = $this->currentRoute->getParameter('group');
      // Not group, then we leave.
      if (!$group instanceof Group) {
        return;
      }
      $url = Url::fromRoute('view.social_group_invitations.page_1', ['group' => $group->id()]);
    }

    // If the current user has no access we leave it be.
    if (NULL !== $url && $url->access($this->currentUser) === FALSE) {
      // This basically means that the normal flow remains intact.
      return;
    }
    // Redirect.
    $event->setResponse(new RedirectResponse($url->toString()));
  }

}

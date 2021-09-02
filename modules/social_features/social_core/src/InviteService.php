<?php

namespace Drupal\social_core;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class InviteService.
 */
class InviteService {

  /**
   * Request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * InviteService constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   ModuleHandler.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(RequestStack $request_stack, ModuleHandlerInterface $moduleHandler, AccountProxyInterface $currentUser) {
    $this->requestStack = $request_stack;
    $this->moduleHandler = $moduleHandler;
    $this->currentUser = $currentUser;
  }

  /**
   * The Invite data for redirect.
   *
   * @param string $specific
   *   Can be either name or amount.
   *
   * @return array|string
   *   Array containing the route name and or invite amount.
   */
  public function getInviteData($specific = '') {
    // Empty by default, we will decorate this in our custom extensions.
    // these can decide on priority what the baseRoute should be.
    $route = [
      'amount' => 0,
      'name' => '',
    ];
    // Default routes. These will be overridden when there are
    // invites available, but we need to determine defaults so we can
    // render the Invite accountheader block link pointing to the overview
    // that is available by the plugins.
    // @todo make this more pretty and generic.
    if ($this->moduleHandler->moduleExists('social_event_invite')) {
      $route['name'] = 'view.user_event_invites.page_user_event_invites';
    }
    if ($this->moduleHandler->moduleExists('social_group_invite')) {
      $route['name'] = 'view.social_group_user_invitations.page_1';
    }

    // If module is enabled and it has invites, lets add the route.
    if ($this->moduleHandler->moduleExists('social_event_invite')) {
      if (\Drupal::hasService('social_event.status_helper')) {
        /** @var \Drupal\social_event\EventEnrollmentStatusHelper $eventHelper */
        $eventHelper = \Drupal::service('social_event.status_helper');
        $event_invites = $eventHelper->getAllUserEventEnrollments($this->currentUser->id());
        if (NULL !== $event_invites && $event_invites > 0) {
          $route['amount'] += count($event_invites);
          // Override the route, because we have available invites!
          $route['name'] = 'view.user_event_invites.page_user_event_invites';
        }
      }
    }
    if ($this->moduleHandler->moduleExists('social_group_invite')) {
      if (\Drupal::hasService('ginvite.invitation_loader')) {
        /** @var \Drupal\ginvite\GroupInvitationLoader $loader */
        $loader = \Drupal::service('ginvite.invitation_loader');
        $group_invites = count($loader->loadByUser());
        if (NULL !== $group_invites && $group_invites > 0) {
          $route['amount'] += $group_invites;
          // Override the route, because we have available invites!
          $route['name'] = 'view.social_group_user_invitations.page_1';
        }
      }
    }

    // Return specific data.
    if ($specific === 'name') {
      return $route['name'];
    }
    if ($specific === 'amount') {
      return $route['amount'];
    }

    return $route;

  }

}

<?php

namespace Drupal\social_core;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ginvite\GroupInvitationLoader;
use Drupal\social_event\EventEnrollmentStatusHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Invite service.
 */
class InviteService {

  /**
   * Request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Providers service to get the enrollments for a user.
   *
   * @var \Drupal\social_event\EventEnrollmentStatusHelper|null
   */
  protected ?EventEnrollmentStatusHelper $eventEnrollmentStatusHelper;

  /**
   * Loader for wrapped GroupContent entities using the 'group_invitation'.
   *
   * @var \Drupal\ginvite\GroupInvitationLoader|null
   */
  protected ?GroupInvitationLoader $groupInvitationLoader;

  /**
   * InviteService constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   ModuleHandler.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\social_event\EventEnrollmentStatusHelper|null $event_enrollment_status_helper
   *   Providers service to get the enrollments for a user.
   * @param \Drupal\ginvite\GroupInvitationLoader|null $group_invitation_loader
   *   Loader for wrapped GroupContent entities using the 'group_invitation'
   *   plugin.
   */
  public function __construct(
    RequestStack $request_stack,
    ModuleHandlerInterface $moduleHandler,
    AccountProxyInterface $currentUser,
    EventEnrollmentStatusHelper $event_enrollment_status_helper = NULL,
    GroupInvitationLoader $group_invitation_loader = NULL
  ) {
    $this->requestStack = $request_stack;
    $this->moduleHandler = $moduleHandler;
    $this->currentUser = $currentUser;
    $this->eventEnrollmentStatusHelper = $event_enrollment_status_helper;
    $this->groupInvitationLoader = $group_invitation_loader;
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
    if ($this->eventEnrollmentStatusHelper !== NULL) {
      $event_invites = $this->eventEnrollmentStatusHelper->getAllUserEventEnrollments((string) $this->currentUser->id());
      if (NULL !== $event_invites && $event_invites > 0) {
        $route['amount'] += count($event_invites);
        // Override the route, because we have available invites!
        $route['name'] = 'view.user_event_invites.page_user_event_invites';
      }
    }
    if ($this->groupInvitationLoader !== NULL) {
      $group_invites = count($this->groupInvitationLoader->loadByUser());
      if (NULL !== $group_invites && $group_invites > 0) {
        $route['amount'] += $group_invites;
        // Override the route, because we have available invites!
        $route['name'] = 'view.social_group_user_invitations.page_1';
      }
    }

    // Return specific data.
    if ($specific === 'name') {
      return (string) $route['name'];
    }
    if ($specific === 'amount') {
      return (string) $route['amount'];
    }

    return $route;

  }

}

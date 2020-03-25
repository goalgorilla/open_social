<?php

namespace Drupal\social_event_invite\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Event owner or organizer access plugin.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "event_owner_or_organizer",
 *   title = @Translation("Event Owner or Organizer"),
 *   help = @Translation("Access for event owner or organizers only.")
 * )
 */
class EventOwnerOrOrganizerAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Allow here for at least LU, real access check in alterRouteDefinition().
    return $account->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', '\Drupal\social_event_invite\Access\EventInvitesOverview::access');
  }

}

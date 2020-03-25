<?php

namespace Drupal\social_event_invite\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Class EventInvitesOverview
 *
 * @package Drupal\social_event_invite\Access
 */
class EventInvitesOverview implements AccessInterface {

  /**
   * Custom access check on the event invites overview.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access() {
    return AccessResult::allowedIf(social_event_owner_or_organizer());
  }
}

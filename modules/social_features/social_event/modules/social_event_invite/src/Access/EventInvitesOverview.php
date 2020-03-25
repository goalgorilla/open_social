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
   */
  public function access() {
    // @todo: do the proper access checks.
    return AccessResult::allowed();
  }
}

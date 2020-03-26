<?php

namespace Drupal\social_event_invite\Access;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\social_event_invite\SocialEventInviteAccessHelper;

/**
 * Class SocialEventInvitesAccess
 *
 * @package Drupal\social_event_invite\Access
 */
class SocialEventInvitesAccess {

  /**
   * The event invite access helper.
   *
   * @var \Drupal\social_event_invite\SocialEventInviteAccessHelper
   */
  protected $accessHelper;

  /**
   * EventInvitesAccess constructor.
   *
   * @param \Drupal\social_event_invite\SocialEventInviteAccessHelper $accessHelper
   *   The event invite access helper.
   */
  public function __construct(SocialEventInviteAccessHelper $accessHelper) {
    $this->accessHelper = $accessHelper;
  }

  /**
   * Custom access check on the invite features on events.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns the result of the access helper.
   * @see \Drupal\social_event_invite\SocialEventInviteAccessHelper::eventFeatureAccess()
   */
  public function eventFeatureAccess() {
    try {
      return $this->accessHelper->eventFeatureAccess();
    } catch (InvalidPluginDefinitionException $e) {
      return AccessResult::neutral();
    } catch (PluginNotFoundException $e) {
      return AccessResult::neutral();
    }
  }

  /**
   * Custom access check for the user invite overview.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns the result of the access helper.
   * @see \Drupal\social_event_invite\SocialEventInviteAccessHelper::userInviteAccess()
   */
  public function userInviteAccess() {
    return $this->accessHelper->userInviteAccess();
  }

}

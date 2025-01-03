<?php

namespace Drupal\social_group_invite\Access;

use Drupal\social_group_invite\SocialGroupInviteAccessHelper;

/**
 * Class SocialGroupInvitesAccess.
 *
 * @package Drupal\social_group_invite\Access
 */
class SocialGroupInvitesAccess {

  /**
   * The group invite access helper.
   *
   * @var \Drupal\social_group_invite\SocialGroupInviteAccessHelper
   */
  protected $accessHelper;

  /**
   * SocialGroupInvitesAccess constructor.
   *
   * @param \Drupal\social_group_invite\SocialGroupInviteAccessHelper $accessHelper
   *   The group invite access helper.
   */
  public function __construct(SocialGroupInviteAccessHelper $accessHelper) {
    $this->accessHelper = $accessHelper;
  }

  /**
   * Custom access check for the user invite overview.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns the result of the access helper.
   *
   * @see \Drupal\social_group_invite\SocialGroupInviteAccessHelper::userInviteAccess()
   */
  public function userInviteAccess() {
    return $this->accessHelper->userInviteAccess();
  }

}

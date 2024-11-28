<?php

namespace Drupal\social_group_invite\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\ginvite\Controller\InvitationOperations;
use Drupal\group\Entity\GroupRelationshipInterface;

/**
 * Handles Accept/Decline operations and Access check for Social groups.
 */
class SocialGroupInvitationController extends InvitationOperations {

  /**
   * {@inheritDoc}
   */
  public function checkAccess(GroupRelationshipInterface $group_content): AccessResult {
    $result = parent::checkAccess($group_content);
    $group = $group_content->getGroup();

    if (!$group->hasPermission('join group', $this->currentUser())) {
      AccessResult::forbidden();
    }

    return $result;
  }

}

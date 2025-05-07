<?php

namespace Drupal\social_group_invite\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ginvite\Controller\InvitationOperations;
use Drupal\group\Entity\GroupInterface;
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

  /**
   * Renders title for the group invite member route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Rendered translatable title.
   */
  public function invitationTitle(GroupInterface $group): TranslatableMarkup {
    $title = $this->t('Invite members');

    if (NULL !== $group->label()) {
      $title = $this->t('Invite members to @group_title', ['@group_title' => $group->label()]);
    }

    return $title;
  }

}

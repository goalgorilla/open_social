<?php

namespace Drupal\social_group_invite\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ginvite\Controller\InvitationOperations;

/**
 * Handles Accept operation for invited users.
 */
class SocialGroupInvitationOperations extends InvitationOperations {

  /**
   * Create user membership and change invitation status.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request.
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_content
   *   Invitation entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   */
  public function accepted(Request $request, GroupRelationshipInterface $group_content) {
    $group = $group_content->getGroup();

    // Check if user already is a member.
    $membership = $this->membershipLoader->load($group, $this->currentUser());
    $relation_type_id = $this->entityTypeManager()->getStorage('group_content_type')->getRelationshipTypeId($group_type->id(), 'group_membership');

    if (!$membership) {
      /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_content */
      $group_membership = GroupRelationship::create([
        'type' => $relation_type_id,
        'entity_id' => $group_content->getEntityId(),
        'content_plugin' => 'group_membership',
        'gid' => $group->id(),
        'uid' => $group_content->getOwnerId(),
        'group_roles' => $group_content->get('group_roles')->getValue(),
      ]);
      $group_membership->save();
    }
    else {
      $this->messenger->addStatus($this->t('You are already a member of the @group.', [
        '@group' => $group->label(),
      ]));
    }

    return new RedirectResponse($group->toUrl()->toString());
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(GroupRelationshipInterface $group_content) {
    $invited = $group_content->getEntityId();

    // Only allow user accept/decline own invitations.
    if ($invited == $this->currentUser()->id()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}

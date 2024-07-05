<?php

namespace Drupal\grequest;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\user\UserInterface;

/**
 * Membership Request Manager class.
 */
class MembershipRequestManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * PrivacyManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Get membership request.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface|null
   *   Group relationship or NULL.
   */
  public function getMembershipRequest(AccountInterface $user, GroupInterface $group) {
    $group_type = $group->getGroupType();
    if (!$group_type->hasPlugin('group_membership_request')) {
      return NULL;
    }

    // If no responsible group relationship types were found, we return nothing.
    $group_membership_requests = $this->entityTypeManager->getStorage('group_content')->loadByProperties([
      'type' => $this->entityTypeManager->getStorage('group_content_type')->getRelationshipTypeId($group_type->id(), 'group_membership_request'),
      'entity_id' => $user->id(),
      'gid' => $group->id(),
    ]);

    if (!empty($group_membership_requests)) {
      return reset($group_membership_requests);
    }

    return NULL;
  }

  /**
   * Approve a membership request.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   Group membership request group relationship.
   * @param array $group_roles
   *   Group roles to be added to a member.
   *
   * @return bool
   *   Result.
   */
  public function approve(GroupRelationshipInterface $group_relationship, array $group_roles = []) {
    $this->updateStatus($group_relationship, GroupMembershipRequest::TRANSITION_APPROVE);
    $result = $group_relationship->save() == SAVED_UPDATED;
    if ($result) {
      // Adding user to a group.
      $group_relationship->getGroup()->addMember($group_relationship->getEntity(), [
        'group_roles' => $group_roles,
      ]);
    }

    return $result;
  }

  /**
   * Reject a membership request.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   Group membership request group relationship.
   *
   * @return bool
   *   Result.
   */
  public function reject(GroupRelationshipInterface $group_relationship) {
    $this->updateStatus($group_relationship, GroupMembershipRequest::TRANSITION_REJECT);
    return $group_relationship->save() == SAVED_UPDATED;
  }

  /**
   * Update status of a membership request.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   Group membership request group relationship.
   * @param string $transition_id
   *   Transition approve | reject.
   */
  public function updateStatus(GroupRelationshipInterface $group_relationship, $transition_id) {
    if ($group_relationship->getPluginId() != 'group_membership_request') {
      throw new \Exception('Only group relationship of "Group membership request" is allowed.');
    }
    $state_item = $group_relationship->get(GroupMembershipRequest::STATUS_FIELD)->first();
    if ($state_item->isTransitionAllowed($transition_id)) {
      $state_item->applyTransitionById($transition_id);
      $group_relationship->set('grequest_updated_by', $this->currentUser->id());
    }
    else {
      throw new \Exception(new FormattableMarkup('Transition ":transition_id" is not allowed.', [':transition_id' => $transition_id]));
    }
  }

  /**
   * Create group membership request group relationship.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param \Drupal\user\UserInterface $user
   *   User.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface
   *   Group membership request group relationship.
   */
  public function create(GroupInterface $group, UserInterface $user) {
    if (!$group->getGroupType()->hasPlugin('group_membership_request')) {
      throw new \Exception('Group membership request plugin is not installed');
    }

    if ($group->getMember($user)) {
      throw new \Exception('This user is already a member of the group');
    }

    $plugin_id = 'group_membership_request';
    $relationship_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $group_type_id = $group->getGroupType()->id();

    $group_relationship = GroupRelationship::create([
      'type' => $relationship_type_storage->getRelationshipTypeId($group_type_id, $plugin_id),
      'gid' => $group->id(),
      'entity_id' => $user->id(),
      GroupMembershipRequest::STATUS_FIELD => GroupMembershipRequest::REQUEST_NEW,
    ]);

    // We have to set transition here. Once the group_content saved events will
    // be correctly fired.
    $this->updateStatus($group_relationship, GroupMembershipRequest::TRANSITION_CREATE);

    return $group_relationship;
  }

}

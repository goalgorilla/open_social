<?php

declare(strict_types=1);

namespace Drupal\social_group\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRole;
use Drupal\social_group\TestControlInterface\Dto\GroupMemberRow;
use Drupal\social_group\TestControlInterface\Success\GroupMemberRoleUpdatedSuccess;
use Drupal\social_group\TestControlInterface\Success\GroupMembersUpdatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Manages who belongs to a group, and with which role.
 */
final class GroupMembership {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
  ) {
  }

  /**
   * Adds members to groups, one membership per row.
   *
   * A user who already belongs to the group is left as they are.
   *
   * @param list<GroupMemberRow> $body
   *   The list of group/user pairs to add as members.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupMembersUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'group_members_add',
    patterns: ['group members:'],
    type: OperationType::Write,
  )]
  public function addGroupMembers(
    #[Example([['group' => 'My group', 'user' => 'Jane Doe']])]
    array $body,
  ): GroupMembersUpdatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No membership rows in request body; refusing to return success with zero members added.');
    }
    foreach ($body as $row) {
      $this->addMemberFromRow($row);
    }

    return new GroupMembersUpdatedSuccess();
  }

  /**
   * Removes members from groups, one membership per row.
   *
   * A user who does not belong to the group is left as they are.
   *
   * @param list<GroupMemberRow> $body
   *   The list of group/user pairs to remove as members.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupMembersUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'group_members_remove',
    patterns: ['I remove group members:'],
    type: OperationType::Write,
  )]
  public function removeGroupMembers(
    #[Example([['group' => 'My group', 'user' => 'Jane Doe']])]
    array $body,
  ): GroupMembersUpdatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No membership rows in request body; refusing to return success with zero members removed.');
    }
    foreach ($body as $row) {
      $this->removeMemberFromRow($row);
    }

    return new GroupMembersUpdatedSuccess();
  }

  /**
   * Adds a single member from a `group members:` row.
   */
  private function addMemberFromRow(GroupMemberRow $row): void {
    $this->loadGroupByTitle($row->group)->addMember($this->loadUserByName($row->user));
  }

  /**
   * Removes a single member from an `I remove group members:` row.
   */
  private function removeMemberFromRow(GroupMemberRow $row): void {
    $this->loadGroupByTitle($row->group)->removeMember($this->loadUserByName($row->user));
  }

  /**
   * Adds the current user as a member of a group.
   *
   * Joining a group requires an account to join as, so an anonymous current
   * user is rejected.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupMembersUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'group_member_add_current_user',
    patterns: ['I am a member of :group'],
    type: OperationType::Write,
  )]
  public function addCurrentUserAsGroupMember(
    #[Example('My group')]
    string $group,
  ): GroupMembersUpdatedSuccess {
    $this->loadGroupByTitle($group)->addMember($this->loadCurrentUser());

    return new GroupMembersUpdatedSuccess();
  }

  /**
   * Adds the current user as a member of a group, with a role.
   *
   * Joining a group requires an account to join as, so an anonymous current
   * user is rejected.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupMembersUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'group_member_add_current_user_with_role',
    patterns: ['I am a member of :group with the :group_role role'],
    type: OperationType::Write,
  )]
  public function addCurrentUserAsGroupMemberWithRole(
    #[Example('My group')]
    string $group,
    #[Example('flexible_group-admin')]
    string $group_role,
  ): GroupMembersUpdatedSuccess {
    $account = $this->loadCurrentUser();
    $group_entity = $this->loadGroupByTitle($group);
    if (GroupRole::load($group_role) === NULL) {
      throw new BadRequestHttpException(sprintf("Could not find group role '%s'.", $group_role));
    }

    $group_entity->addMember($account, ['group_roles' => [$group_role]]);

    return new GroupMembersUpdatedSuccess();
  }

  /**
   * Adds a group role to a group member.
   *
   * A member who already has the role is left as they are.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupMemberRoleUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'group_member_role_add',
    patterns: ['I add :role_id role to :group_member in :group_name group'],
    type: OperationType::Write,
  )]
  public function addGroupMemberRole(
    #[Example('flexible_group-admin')]
    string $role_id,
    #[Example('Alice')]
    string $group_member,
    #[Example('Designers')]
    string $group_name,
  ): GroupMemberRoleUpdatedSuccess {
    $group = $this->loadGroupByTitle($group_name);
    $user = $this->loadUserByName($group_member);
    $relationship = $this->loadMembershipRelationship($group, $user, $group_member, $group_name);

    $available_roles = $group->getGroupType()->getRoles();
    if (!array_key_exists($role_id, $available_roles)) {
      throw new BadRequestHttpException(sprintf('Role %s does not exist for group type %s.', $role_id, $group->bundle()));
    }

    $existing_roles = $relationship->get('group_roles')->getValue();
    foreach ($existing_roles as $role) {
      if ($role['target_id'] === $role_id) {
        return new GroupMemberRoleUpdatedSuccess();
      }
    }

    $existing_roles[] = ['target_id' => $role_id];
    $relationship->set('group_roles', $existing_roles);
    $relationship->save();

    return new GroupMemberRoleUpdatedSuccess();
  }

  /**
   * Removes a group role from a group member.
   *
   * @return \Drupal\social_group\TestControlInterface\Success\GroupMemberRoleUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'group_member_role_remove',
    patterns: ['I remove :role_id role for :group_member from :group_name group'],
    type: OperationType::Write,
  )]
  public function removeGroupMemberRole(
    #[Example('flexible_group-admin')]
    string $role_id,
    #[Example('Alice')]
    string $group_member,
    #[Example('Developers')]
    string $group_name,
  ): GroupMemberRoleUpdatedSuccess {
    $group = $this->loadGroupByTitle($group_name);
    $user = $this->loadUserByName($group_member);
    $relationship = $this->loadMembershipRelationship($group, $user, $group_member, $group_name);

    $roles = $relationship->get('group_roles')->getValue();
    $role_found = FALSE;

    foreach ($roles as $key => $current_role) {
      if ($current_role['target_id'] === $role_id) {
        unset($roles[$key]);
        $role_found = TRUE;
        break;
      }
    }

    if (!$role_found) {
      throw new BadRequestHttpException(sprintf('Role %s not found for user %s in group %s.', $role_id, $group_member, $group_name));
    }

    $relationship->set('group_roles', array_values($roles));
    $relationship->save();

    return new GroupMemberRoleUpdatedSuccess();
  }

  /**
   * Load the account the request is made as.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the request is made anonymously.
   */
  private function loadCurrentUser(): UserInterface {
    if (!$this->currentUser->isAuthenticated()) {
      throw new BadRequestHttpException('This operation joins a group as the current user, so it can not be called anonymously.');
    }

    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if (!$account instanceof UserInterface) {
      throw new BadRequestHttpException(sprintf('Could not load the current user (%d).', $this->currentUser->id()));
    }

    return $account;
  }

  /**
   * Load the group_membership relationship for a user in a group.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the user does not belong to the group.
   */
  private function loadMembershipRelationship(GroupInterface $group, UserInterface $user, string $group_member, string $group_name): GroupMembershipInterface {
    $relationships = $group->getRelationshipsByEntity($user, 'group_membership');
    $relationship = reset($relationships);
    if (!$relationship instanceof GroupMembershipInterface) {
      throw new BadRequestHttpException(sprintf('User %s does not belong to group %s.', $group_member, $group_name));
    }

    return $relationship;
  }

  /**
   * Load a group entity by its title.
   *
   * @param string $label
   *   The group label.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if no group, or more than one group, shares the given title.
   */
  private function loadGroupByTitle(string $label): GroupInterface {
    $group_ids = $this->entityTypeManager->getStorage('group')->getQuery()
      ->accessCheck(FALSE)
      ->condition('label', $label)
      ->execute();

    if ($group_ids === []) {
      throw new BadRequestHttpException(sprintf("Group '%s' not found.", $label));
    }
    if (count($group_ids) > 1) {
      throw new BadRequestHttpException(sprintf("Group title '%s' is ambiguous: %d groups share that title.", $label, count($group_ids)));
    }

    $group = Group::load((int) reset($group_ids));
    if (!$group instanceof GroupInterface) {
      throw new BadRequestHttpException(sprintf("Group '%s' not found.", $label));
    }

    return $group;
  }

  /**
   * Load a user entity by username.
   *
   * @param string $username
   *   The Drupal username to load.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the user is not found.
   */
  private function loadUserByName(string $username): UserInterface {
    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username]);
    $user = reset($users);

    if (!$user instanceof UserInterface) {
      throw new BadRequestHttpException(sprintf('User %s not found.', $username));
    }

    return $user;
  }

}

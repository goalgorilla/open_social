<?php

namespace Drupal\Tests\grequest\Kernel;

use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\GroupMembership;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * Tests the general behavior of group entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\Group
 * @group group
 */
class GroupMembershipRequestTest extends GroupKernelTestBase {

  /**
   * Membership request manager.
   *
   * @var \Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $groupRelationshipTypeStorage;

  /**
   * The group relationship type for group membership request.
   *
   * @var \Drupal\group\Entity\GroupRelationshipTypeInterface
   */
  protected $groupRelationshipType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'grequest',
    'state_machine',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');

    $this->installConfig([
      'grequest',
      'state_machine',
    ]);

    $this->membershipRequestManager = $this->container->get('grequest.membership_request_manager');

    $group_type = $this->createGroupType();
    $this->group = $this->createGroup(['type' => $group_type->id()]);

    $config = [
      'group_cardinality' => 0,
      'entity_cardinality' => 1,
      'remove_group_membership_request' => FALSE,
    ];
    // Enable group membership request group relationship plugin.
    $this->groupRelationshipTypeStorage = $this->entityTypeManager->getStorage('group_content_type');
    $this->groupRelationshipType = $this->groupRelationshipTypeStorage->createFromPlugin($this->group->getGroupType(), 'group_membership_request', $config);
    $this->groupRelationshipType->save();
  }

  /**
   * Test the creation of the membership request when user is the member.
   */
  public function testAddRequestForMember() {
    $account = $this->createUser();
    $this->group->addMember($account);

    $this->expectExceptionMessage('This user is already a member of the group');
    $this->membershipRequestManager->create($this->group, $account);
  }

  /**
   * Test approval.
   */
  public function testRequestApproval() {
    $account = $this->createUser();
    $group_membership_request = $this->createRequestMembership($account);
    $this->membershipRequestManager->approve($group_membership_request);
    $this->assertEquals($group_membership_request->get(GroupMembershipRequest::STATUS_FIELD)->value, GroupMembershipRequest::REQUEST_APPROVED);
  }

  /**
   * Test rejection.
   */
  public function testRequestRejection() {
    $account = $this->createUser();
    $group_membership_request = $this->createRequestMembership($account);
    $this->membershipRequestManager->reject($group_membership_request);
    $this->assertEquals($group_membership_request->get(GroupMembershipRequest::STATUS_FIELD)->value, GroupMembershipRequest::REQUEST_REJECTED);
  }

  /**
   * Test wrong status update workflow.
   */
  public function testWrongRequestWorkflow() {
    $account = $this->createUser();
    $group_membership_request = $this->createRequestMembership($account);
    $this->membershipRequestManager->approve($group_membership_request);
    $this->assertEquals($group_membership_request->get(GroupMembershipRequest::STATUS_FIELD)->value, GroupMembershipRequest::REQUEST_APPROVED);
    $this->expectExceptionMessage('Transition "reject" is not allowed.');
    $this->membershipRequestManager->reject($group_membership_request);
  }

  /**
   * Create group membership request.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface
   *   Group membership request.
   */
  public function createRequestMembership($account) {
    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $this->assertEquals($group_membership_request->get(GroupMembershipRequest::STATUS_FIELD)->value, GroupMembershipRequest::REQUEST_PENDING);

    return $group_membership_request;
  }

  /**
   * Test approval with roles.
   */
  public function testApprovalWithRoles() {
    $account = $this->createUser();
    // Create a custom role.
    $custom_role = $this->createGroupRole([
      'label' => 'Custom role',
      'group_type' => $this->group->getGroupType()->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);

    $group_membership_request = $this->createRequestMembership($account);

    $this->membershipRequestManager->approve($group_membership_request, [$custom_role->id()]);

    $group_membership = $this->group->getMember($account);
    $this->assertTrue($group_membership instanceof GroupMembership, 'Group membership has been successfully created.');

    $this->assertTrue(in_array($custom_role->id(), array_keys($group_membership->getRoles())), 'Role has been found');
  }

  /**
   * Test deletion of group membership request after user deletion.
   */
  public function testUserDeletion() {
    $account = $this->createUser();
    $this->createRequestMembership($account);

    $this->entityTypeManager->getStorage('user')->delete([$account]);

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($membership_request);
  }

  /**
   * Test deletion of group membership request after group membership deletion.
   */
  public function testGroupMembershipDeletion() {
    $account = $this->createUser();

    $group_membership_request = $this->createRequestMembership($account);
    $this->membershipRequestManager->approve($group_membership_request);

    $this->group->removeMember($account);

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($membership_request);
  }

  /**
   * Test the request is removed when membership is removed.
   */
  public function testMembershipRemovalWhenMembershipRemoved() {
    $account = $this->createUser();

    $this->group->addMember($account);

    $this->group->removeMember($account);

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($membership_request);
  }

  /**
   * Test wrong group relationship.
   */
  public function testWrongGroupRelationship() {
    $account = $this->createUser();
    $relation_type_id = $this->groupRelationshipTypeStorage->getRelationshipTypeId($this->group->getGroupType()->id(), 'group_membership');
    $group_membership_group_relationship = GroupRelationship::create([
      'type' => $relation_type_id,
      'gid' => $this->group->id(),
      'entity_id' => $account->id(),
    ]);

    $this->expectExceptionMessage('Only group relationship of "Group membership request" is allowed.');
    $this->membershipRequestManager->approve($group_membership_group_relationship);
  }

  /**
   * Test group membership removal with disabled settings.
   */
  public function testRequestRemovalWithDisabledSettings() {
    $account = $this->createUser();

    // Add first group membership request.
    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    // Add the user as member.
    $this->group->addMember($account);

    // Since removal is disabled we should see find group membership request.
    $group_membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNotNull($group_membership_request);
  }

  /**
   * Test group membership removal with enabled settings.
   */
  public function testRequestRemovalWithEnabledSettings() {
    $config = [
      'group_cardinality' => 0,
      'entity_cardinality' => 1,
      'remove_group_membership_request' => TRUE,
    ];
    $this->groupRelationshipType->updatePlugin($config);
    $account = $this->createUser();

    // Add first group membership request.
    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    // Add the user as member.
    $this->group->addMember($account);

    // Since removal is disabled we should see find group membership request.
    $group_membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($group_membership_request);
  }

}

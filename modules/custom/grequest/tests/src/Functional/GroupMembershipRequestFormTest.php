<?php

namespace Drupal\Tests\grequest\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\GroupMembership;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the behavior of the group type form.
 *
 * @group group
 */
class GroupMembershipRequestFormTest extends GroupBrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'group',
    'group_test_config',
    'state_machine',
    'grequest',
  ];

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->group = $this->createGroup(['type' => 'default']);
    $group_type = $this->group->getGroupType();
    $plugin_id = 'group_membership_request';
    $this->membershipRequestManager = $this->container->get('grequest.membership_request_manager');

    // Install group_membership_request group relationship.

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $group_relationship_type_storage */
    $group_relationship_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $config = [
      'group_cardinality' => 0,
      'entity_cardinality' => 1,
    ];

    $group_relationship_type_storage->createFromPlugin($group_type, $plugin_id, $config)->save();

    // Add a text field to the group relationship type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test_text',
      'entity_type' => 'group_content',
      'type' => 'text',
    ]);
    $field_storage->save();

    $relation_type_id = $group_relationship_type_storage->getRelationshipTypeId($group_type->id(), $plugin_id);
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $relation_type_id,
      'label' => 'String long',
    ])->save();

    EntityFormDisplay::create([
      'targetEntityType' => 'group_content',
      'bundle' => $relation_type_id,
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_test_text', ['type' => 'text_textfield'])->enable()->save();

    // Add permissions to members.
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'administer membership requests',
        'leave group',
      ],
    ]);

    // Allow outsider request membership.
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['request group membership'],
    ]);
  }

  /**
   * Tests approval form.
   */
  public function testApprovalForm() {
    $account = $this->createUser();
    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $manager_account = $this->createUser();
    $this->group->addMember($manager_account);
    $this->drupalLogin($manager_account);

    // Create a custom role.
    $custom_role = $this->createGroupRole([
      'label' => 'Custom role',
      'group_type' => $this->group->getGroupType()->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);

    $this->drupalGet("/group/{$this->group->id()}/content/{$group_membership_request->id()}/approve-membership");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Approve';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->linkExists('Cancel');
    $this->assertSession()->linkByHrefExists($this->group->toUrl()->toString());
    $this->assertSession()->pageTextContains(strip_tags($this->t('Are you sure you want to approve a request for %user?', ['%user' => $account->getDisplayName()])->render()));

    $edit = [
      "roles[{$custom_role->id()}]" => 1,
    ];

    $this->submitForm($edit, $submit_button);
    $this->assertSession()->pageTextContains($this->t('Membership request approved'));

    $group_membership = $this->group->getMember($account);
    $this->assertTrue($group_membership instanceof GroupMembership, 'Group membership has been successfully created.');
  }

  /**
   * Tests reject form.
   */
  public function testRejectForm() {
    $account = $this->createUser();
    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $manager_account = $this->createUser();
    $this->group->addMember($manager_account);
    $this->drupalLogin($manager_account);

    $this->drupalGet("/group/{$this->group->id()}/content/{$group_membership_request->id()}/reject-membership");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Reject';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->linkExists('Cancel');
    $this->assertSession()->linkByHrefExists($this->group->toUrl()->toString());
    $this->assertSession()->pageTextContains(strip_tags($this->t('Are you sure you want to reject a request for %user?', ['%user' => $account->getDisplayName()])->render()));

    $this->submitForm([], $submit_button);
    $this->assertSession()->pageTextContains($this->t('Membership request rejected'));

    $group_membership = $this->group->getMember($account);
    $this->assertFalse($group_membership, 'Group membership was not found.');
  }

  /**
   * Tests request form.
   */
  public function testRequestForm() {
    // Access request form as a member.
    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->assertSession()->statusCodeEquals(403);

    // Access request form as a not member.
    $account = $this->createUser();
    $this->drupalLogin($account);

    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Request group membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->linkExists('Cancel');
    $this->assertSession()->linkByHrefExists($this->group->toUrl()->toString());
    $this->assertSession()->pageTextContains(strip_tags($this->t('Request membership for group %group', ['%group' => $this->group->label()])->render()));
    $this->assertSession()->fieldExists('field_test_text[0][value]');
    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($membership_request);

    $this->submitForm([], $submit_button);
    $this->assertSession()->pageTextContains($this->t('Your request is waiting for approval'));

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertTrue($membership_request instanceof GroupRelationshipInterface, 'Membership request has been successfully created.');

    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests request after leave of the group.
   */
  public function testRequestAfterLeaveForm() {
    $account = $this->createUser();
    $this->drupalLogin($account);

    // Request membership and approve it.
    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->submitForm([], 'Request group membership');
    $this->assertSession()->pageTextContains($this->t('Your request is waiting for approval'));

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->membershipRequestManager->approve($membership_request);

    // Leave the group.
    $this->drupalGet("/group/{$this->group->id()}/leave");
    $this->submitForm([], 'Leave group');

    // Try to request membership again.
    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->submitForm([], 'Request group membership');
    $this->assertSession()->pageTextContains($this->t('Your request is waiting for approval'));
  }

}

<?php

namespace Drupal\social_group\tests\Kernel;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupType;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the Group revision view revision page.
 *
 * @group group
 */
class SocialGroupRevisionViewTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'flexible_permissions',
    'group',
    'options',
    'flag',
    'social_group',
  ];

  /**
   * Test revision access check.
   */
  public function testAccessCheck(): void {
    // Create a global user role to attach to the group role.
    $global_role = Role::create([
      'id' => 'group_member',
      'label' => 'Group Member',
    ]);
    $global_role->grantPermission('access content');
    $global_role->save();

    // Create a group type.
    $group_type = GroupType::create([
      'id' => 'example',
      'label' => 'Example Group Type',
    ]);
    $group_type->save();

    // Create a group role with required fields.
    $group_role = GroupRole::create([
      'id' => 'example-insider',
      'label' => 'Example Insider Role',
      'group_type' => 'example',
      'scope' => 'insider',
      'global_role' => 'group_member',
    ]);
    // Adding permission to view group and revisions.
    $group_role->grantPermission('view group revisions');
    $group_role->grantPermission('view group');
    $group_role->save();

    // Create the test group.
    $group = Group::create([
      'type' => 'example',
      'label' => 'Test Group',
    ]);
    $group->save();

    // Create a group member user with a global role.
    $user = User::create([
      'name' => 'Group Member',
    ]);
    $user->addRole('group_member');
    $user->save();

    // Add user to the group.
    $group->addMember($user);

    // Use the access control handler to check access.
    $access_handler = \Drupal::entityTypeManager()
      ->getAccessControlHandler('group');

    // Check 'view revision' operation access for the user.
    // For the test we are enabling 'social_group' module.
    // So SocialGroupAccessControlHandler is enabled.
    // If everything is OK user should have access to view the revision.
    $access_result = $access_handler->access($group, 'view revision', $user, TRUE);
    $this->assertTrue($access_result->isAllowed());
  }

}

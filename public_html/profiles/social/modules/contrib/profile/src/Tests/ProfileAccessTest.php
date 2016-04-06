<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileAccessTest.
 */

namespace Drupal\profile\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\profile\Entity\Profile;
use Drupal\user\RoleInterface;

/**
 * Tests profile access handling.
 *
 * @group profile
 */
class ProfileAccessTest extends ProfileTestBase {

  /**
   * The access control handler.
   *
   * @var \Drupal\profile\ProfileAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)->set('permissions', array())->save();

    $this->accessControlHandler = \Drupal::entityTypeManager()->getAccessControlHandler('profile');
  }

  /**
   * Tests profile create and permissions.
   */
  public function testProfileCreateAccess() {
    // Test user without any permissions.
    $web_user1 = $this->drupalCreateUser([]);

    // Verify user does not have access to create.
    $access = $this->accessControlHandler->createAccess($this->type->id(), $web_user1);
    $this->assertFalse($access);

    // Verify access through route.
    $this->drupalLogin($web_user1);
    $this->drupalGet("user/{$web_user1->id()}/{$this->type->id()}");
    $this->assertResponse(403);

    // Test user with permission to only add own profile.
    $web_user2 = $this->drupalCreateUser(["add own {$this->type->id()} profile"]);

    // Verify user has access to add their own profile.
    $access = $this->accessControlHandler->createAccess($this->type->id(), $web_user2);
    $this->assertTrue($access);

    // Verify user cannot create another user's profile.
    $access = $this->accessControlHandler->createAccess($this->type->id(), $web_user1);
    $this->assertFalse($access);

    // Verify access through route.
    $this->drupalLogin($web_user2);
    $this->drupalGet("user/{$web_user2->id()}/{$this->type->id()}");
    $this->assertResponse(200);
    $this->drupalGet("user/{$web_user1->id()}/{$this->type->id()}");
    $this->assertResponse(403);

    // Create a new profile type.
    $this->createProfileType('test2', 'Test2 profile', TRUE);
    $access = $this->accessControlHandler->createAccess('test2', $web_user2);
    $this->assertFalse($access);
    $this->drupalGet("user/{$web_user2->id()}/test2");
    $this->assertResponse(403);

    // Test user with permission to only add any profile.
    $web_user3 = $this->drupalCreateUser(["add any {$this->type->id()} profile"]);
    $access = $this->accessControlHandler->createAccess($this->type->id(), $web_user3);
    $this->assertTrue($access);
    $access = $this->accessControlHandler->createAccess($this->type->id(), $web_user2);
    $this->assertTrue($access);

    // Verify access through route.
    $this->drupalLogin($web_user3);
    $this->drupalGet("user/{$web_user3->id()}/{$this->type->id()}");
    $this->assertResponse(200);
    $this->drupalGet("user/{$web_user2->id()}/{$this->type->id()}");
    $this->assertResponse(200);
  }

  /**
   * Tests profile view access.
   */
  public function testProfileViewAccess() {
    // Setup users.
    $web_user1 = $this->drupalCreateUser([
      "view own {$this->type->id()} profile",
    ]);
    $web_user2 = $this->drupalCreateUser([
      "view any {$this->type->id()} profile",
    ]);

    // Setup profiles.
    $profile1 = Profile::create([
      'uid' => $web_user1->id(),
      'type' => $this->type->id(),
    ]);
    $profile1->set($this->field->getName(), $this->randomString());
    $profile1->save();
    $profile2 = Profile::create([
      'uid' => $web_user2->id(),
      'type' => $this->type->id(),
    ]);
    $profile2->set($this->field->getName(), $this->randomString());
    $profile2->save();

    // Test user1 can only view own profiles.
    $access = $profile1->access('view', $web_user1);
    $this->assertTrue($access);
    $access = $profile2->access('view', $web_user1);
    $this->assertFalse($access);

    // Test user2 can view any profiles.
    $access = $profile1->access('view', $web_user2);
    $this->assertTrue($access);
    $access = $profile2->access('view', $web_user2);
    $this->assertTrue($access);
  }

  /**
   * Tests profile edit flow and permissions.
   */
  public function testProfileEditAccess() {
    // Setup users.
    $web_user1 = $this->drupalCreateUser([
      "add own {$this->type->id()} profile",
      "edit own {$this->type->id()} profile",
    ]);
    $web_user2 = $this->drupalCreateUser([
      "add any {$this->type->id()} profile",
      "edit any {$this->type->id()} profile",
    ]);

    // Setup profiles.
    $profile1 = Profile::create([
      'uid' => $web_user1->id(),
      'type' => $this->type->id(),
    ]);
    $profile1->set($this->field->getName(), $this->randomString());
    $profile1->save();
    $profile2 = Profile::create([
      'uid' => $web_user2->id(),
      'type' => $this->type->id(),
    ]);
    $profile2->set($this->field->getName(), $this->randomString());
    $profile2->save();

    // Test user1 can only edit own profiles.
    $access = $profile1->access('edit', $web_user1);
    $this->assertTrue($access);
    $access = $profile2->access('edit', $web_user1);
    $this->assertFalse($access);

    // Test user2 can edit any profiles.
    $access = $profile1->access('edit', $web_user2);
    $this->assertTrue($access);
    $access = $profile2->access('edit', $web_user2);
    $this->assertTrue($access);
  }

  /**
   * Tests the non-multiple profile type create and edit flow.
   */
  public function testProfileNotMultipleFlow() {
    $web_user1 = $this->drupalCreateUser([
      "add own {$this->type->id()} profile",
      "edit own {$this->type->id()} profile",
    ]);
    $this->drupalLogin($web_user1);

    // Create the profile.
    $edit = [
      "{$this->field->getName()}[0][value]" => $this->randomString(),
    ];
    $this->drupalPostForm("user/{$web_user1->id()}/{$this->type->id()}", $edit, 'Save and make default');
    $this->assertRaw(new FormattableMarkup('%type profile has been created.', [
      '%type' => $this->type->label(),
    ]));

    // Update the profile.
    $edit = [
      "{$this->field->getName()}[0][value]" => $this->randomString(),
    ];
    $this->drupalPostForm("user/{$web_user1->id()}/{$this->type->id()}", $edit, t('Save'));
    $this->assertRaw(new FormattableMarkup('%type profile has been updated.', [
      '%type' => $this->type->label(),
    ]));
  }

  /**
   * Tests administrative-only profiles.
   */
  public function testAdminOnlyProfiles() {
    $id = $this->type->id();
    $field_name = $this->field->getName();

    // Create a test user account.
    $web_user = $this->drupalCreateUser(['access user profiles']);
    $uid = $web_user->id();
    $value = $this->randomMachineName();

    // Administratively enter profile field values for the new account.
    $this->drupalLogin($this->adminUser);

    $edit = [
      "{$this->field->getName()}[0][value]" => $value,
    ];
    $this->drupalPostForm("user/$uid/$id", $edit, 'Save and make default');

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = \Drupal::entityTypeManager()
      ->getStorage('profile')
      ->loadByUser($web_user, $this->type->id());
    $profile_id = $profile->id();

    $this->assertEqual($profile->getType(), $this->type->id());

    /*
// Verify that the administrator can see the profile.
$this->drupalGet("user/$uid");
$this->assertText($this->type->label());
$this->assertText($value);
$this->drupalLogout();
    // Verify that the user can not access, create or edit the profile.
    $this->drupalLogin($web_user);
    $this->drupalGet("user/$uid");
    $this->assertNoText($this->type->label());
    $this->assertNoText($value);
    $this->drupalGet("user/$uid/edit/profile/$id/$profile_id");
    $this->assertResponse(403);

    // Check edit link isn't displayed.
    $this->assertNoLinkByHref("user/$uid/edit/profile/$id/$profile_id");
    // Check delete link isn't displayed.
    $this->assertNoLinkByHref("user/$uid/delete/profile/$id/$profile_id");


    // Allow users to edit own profiles.
    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ["edit own $id profile"]);

    // Verify that the user is able to edit the own profile.
    $value = $this->randomMachineName();
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->drupalPostForm("user/$uid/edit/profile/$id/$profile_id", $edit, t('Save'));
    $this->assertText(new FormattableMarkup('profile has been updated.', []));


    // Verify that the own profile is still not visible on the account page.
    $this->drupalGet("user/$uid");
    $this->assertNoText($this->type->label());
    $this->assertNoText($value);

    // Allow users to view own profiles.
    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ["view own $id profile"]);

    // Verify that the own profile is visible on the account page.
    $this->drupalGet("user/$uid");
    $this->assertText($this->type->label());
    $this->assertText($value);

    // Allow users to delete own profiles.
    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ["delete own $id profile"]);

    // Verify that the user can delete the own profile.
    $this->drupalGet("user/$uid/edit/profile/$id/$profile_id");
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertRaw(new FormattableMarkup('@label profile deleted.', ['@label' => $this->type->label()]));
    $this->assertUrl("user/$uid");

    // Verify that the profile is gone.
    $this->drupalGet("user/$uid");
    $this->assertNoText($this->type->label());
    $this->assertNoText($value);
    $this->drupalGet("user/$uid/edit/profile/$id");
    $this->assertNoText($value);
*/
  }

}

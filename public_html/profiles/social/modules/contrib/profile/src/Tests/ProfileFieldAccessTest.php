<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileFieldAccessTest.
 */

namespace Drupal\profile\Tests;

use Drupal\Core\Cache\Cache;

/**
 * Tests profile field access functionality.
 *
 * @group profile
 */
class ProfileFieldAccessTest extends ProfileTestBase {

  private $webUser;
  private $otherUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access user profiles',
      'administer profile types',
      'administer profile fields',
      'administer profile display',
      'bypass profile access',
    ]);

    $user_permissions = [
      'access user profiles',
      "add own {$this->type->id()} profile",
      "edit own {$this->type->id()} profile",
      "view own {$this->type->id()} profile",
    ];

    $this->webUser   = $this->drupalCreateUser($user_permissions);
    $this->otherUser = $this->drupalCreateUser($user_permissions);
  }

  /**
   * Tests private profile field access.
   */
  public function testPrivateField() {
    $this->drupalLogin($this->adminUser);

    // Create a private profile field.
    $edit = [
      'new_storage_type' => 'string',
      'label' => 'Secret',
      'field_name' => 'secret',
    ];
    $this->drupalPostForm("admin/config/people/profiles/types/manage/{$this->type->id()}/fields/add-field", $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $edit = [
      'profile_private' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));

    // Fill in a field value.
    $this->drupalLogin($this->webUser);
    $uid = $this->webUser->id();
    $secret = $this->randomMachineName();
    $edit = [
      'field_secret[0][value]' => $secret,
    ];
    $this->drupalPostForm("user/$uid/{$this->type->id()}", $edit, t('Save'));

    // User cache page need to be cleared to see new profile.
    Cache::invalidateTags([
      'user:' . $uid,
      'user_view',
    ]);

    // Verify that the private field value appears for the profile owner.
    $this->drupalGet("user/$uid");
    $this->assertText($secret);

    // Verify that the private field value appears for the administrator.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("user/$uid");
    $this->assertText($secret);

    // Verify that the private field value does not appear for other users.
    $this->drupalLogin($this->otherUser);
    $this->drupalGet("user/$uid");
    $this->assertNoText($secret);
  }

}

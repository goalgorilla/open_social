<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileRoleAccessTest.
 */

namespace Drupal\profile\Tests;

/**
 * Tests profile role access handling.
 *
 * @group profile
 */
class ProfileRoleAccessTest extends ProfileTestBase {

  /**
   * Randomly generated profile type entity.
   *
   * Requires some, but not all roles.
   *
   * @var \Drupal\profile\Entity\ProfileType
   */
  protected $type2;

  /**
   * Randomly generated profile type entity.
   *
   * Requires all profile roles.
   *
   * @var \Drupal\profile\Entity\ProfileType
   */
  protected $type3;

  /**
   * Randomly generated user role entity.
   *
   * @var \Drupal\user\Entity\Role
   */
  protected $role1;

  /**
   * Randomly generated user role entity.
   *
   * @var \Drupal\user\Entity\Role
   */
  protected $role2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->role1 = $this->drupalCreateRole([]);
    $this->role2 = $this->drupalCreateRole([]);
    $this->type2 = $this->createProfileType(NULL, NULL, FALSE, [$this->role2]);
    $this->type3 = $this->createProfileType(NULL, NULL, FALSE, [$this->role1, $this->role2]);
  }

  /**
   * Tests add profile form access for a profile type that does not require
   * users to have a role.
   */
  public function testProfileWithNoRoles() {
    // Create user with add own profile permissions.
    $web_user1 = $this->drupalCreateUser(["add own {$this->type->id()} profile"]);
    $this->drupalLogin($web_user1);

    // Test user without role can access add profile form.
    // Expected: User can access form.
    $this->drupalGet("user/{$web_user1->id()}/{$this->type->id()}");
    $this->assertResponse(200);
  }

  /**
   * Tests add profile form access for a profile type that requires users to
   * have a single role.
   */
  public function testProfileWithSingleRole() {
    // Create user with add own profile permissions.
    $web_user1 = $this->drupalCreateUser(["add own {$this->type2->id()} profile"]);
    $this->drupalLogin($web_user1);

    // Test user without role can access add profile form.
    // Expected: User cannot access form.
    $this->drupalGet("user/{$web_user1->id()}/{$this->type2->id()}");
    $this->assertResponse(403);

    // Test user with wrong role can access add profile form.
    // Expected: User cannot access form.
    $web_user1->addRole($this->role1);
    $web_user1->save();

    $this->drupalGet("user/{$web_user1->id()}/{$this->type2->id()}");
    $this->assertResponse(403);

    // Test user with correct role can access add profile form.
    // Expected: User can access form.
    $web_user1->removeRole($this->role1);
    $web_user1->addRole($this->role2);
    $web_user1->save();

    $this->drupalGet("user/{$web_user1->id()}/{$this->type2->id()}");
    $this->assertResponse(200);
  }

  /**
   * Tests add profile form access for a profile type that requires users to
   * have one of multiple roles.
   */
  public function testProfileWithAllRoles() {
    // Create user with add own profile permissions.
    $web_user1 = $this->drupalCreateUser(["add own {$this->type3->id()} profile"]);
    $this->drupalLogin($web_user1);

    // Test user without role can access add profile form.
    // Expected: User cannot access form.
    $this->drupalGet("user/{$web_user1->id()}/{$this->type3->id()}");
    $this->assertResponse(403);

    // Test user with role 1 can access add profile form.
    // Expected: User can access form.
    $web_user1->addRole($this->role1);
    $web_user1->save();

    $this->drupalGet("user/{$web_user1->id()}/{$this->type3->id()}");
    $this->assertResponse(200);

    // Test user with both roles can access add profile form.
    // Expected: User can access form.
    $web_user1->addRole($this->role2);
    $web_user1->save();

    $this->drupalGet("user/{$web_user1->id()}/{$this->type3->id()}");
    $this->assertResponse(200);

    // Test user with role 2 can access add profile form.
    // Expected: User can access form.
    $web_user1->removeRole($this->role1);
    $web_user1->save();

    $this->drupalGet("user/{$web_user1->id()}/{$this->type3->id()}");
    $this->assertResponse(200);

    // Test user without role can access add profile form.
    // Expected: User cannot access form.
    $web_user1->removeRole($this->role2);
    $web_user1->save();

    $this->drupalGet("user/{$web_user1->id()}/{$this->type3->id()}");
    $this->assertResponse(403);
  }

}

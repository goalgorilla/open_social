<?php

namespace Drupal\Tests\social_user\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\social_user\Traits\EntityAccessAssertionTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * Test access checks for the user without social_profile.
 *
 * This class tests behaviour in social_user that is modified in social_profile.
 * For example the access to the user's mail field changes when social_profile
 * is enabled.
 *
 * In contrast the `UserAccessCheckTest` should pass without modification when
 * the `social_profile` module is enabled.
 *
 * @package social_user
 */
class UserWithoutProfileAccessCheckTest extends KernelTestBase {

  use UserCreationTrait;
  use EntityAccessAssertionTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    "entity",
    "user",
    "role_delegation",
    "social_user",
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installConfig('system');
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Test that a user without permissions cannot view user emails.
   *
   * This is the default Drupal behaviour that we respect in the social_user
   * module. This behaviour is overwritten by social_profile and those changes
   * are covered by its own tests.
   */
  public function testCannotViewOtherEmailWithoutPermission() : void {
    $viewer = $this->setUpCurrentUser([], ['access user profiles']);
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $this->assertFieldAccess(
      $test_user,
      'mail',
      'view',
      $viewer,
      AccessResult::neutral()
        ->cachePerUser()
    );
  }

}

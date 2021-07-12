<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Tests\social_user\Kernel\UserAccessCheck as BaseUserAccessCheck;
use Drupal\user\UserInterface;

/**
 * Test access checks for the user entity.
 *
 * This extends the UserAccessCheck test in the `user` module since that
 * behaviour should remain valid when this module is enabled.
 *
 * @package social_profile
 */
class UserAccessCheck extends BaseUserAccessCheck {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    "profile",
    "social_profile",
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('profile_type');
    $this->installEntitySchema('profile');
  }

  /**
   * Test that the module allows configuring showing the email for everyone.
   *
   * Field access checks do not require access to the entity because it's
   * assumed that field access checks are not even run if a user doesn't have
   * access to the parent entity.
   */
  public function testShowAllConfigurationForPerimssionedUser() : void {
    $profile_settings = $this->config('social_profile.settings');
    $profile_settings->set('social_profile_show_email', TRUE)
      ->save();

    $test_user = $this->createUser();
    $viewer = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");

    $this->assertFieldAccess(
      $test_user,
      'mail',
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheableDependency($test_user)
        ->addCacheableDependency($profile_settings)
        ->addCacheContexts(['user', 'user.permissions'])
    );
  }

  /**
   * Test that a user who can view users can't view email.
   *
   * @see \Drupal\Tests\social_user\Kernel\UserAccessCheck::testCanViewOwnEmailWithoutPermission
   */
  public function testCanViewOwnEmailWithoutPermission() : void {
    $user = $this->createUser(['access user profiles']);
    self::assertInstanceOf(UserInterface::class, $user, "Test set-up failed: could not create user.");

    $this->assertFieldAccess(
      $user,
      'mail',
      'view',
      $user,
      AccessResult::allowed()
        ->addCacheableDependency($user)
        ->addCacheContexts(['user.permissions'])
        ->addCacheTags(['config:social_profile.settings'])
    );
  }

  /**
   * Test that a user who can view users can't view email.
   *
   * @see \Drupal\Tests\social_user\Kernel\UserAccessCheck::testCannotViewOtherEmailWithoutPermission
   */
  public function testCannotViewOtherEmailWithoutPermission() : void {
    $test_user = $this->createUser();
    $viewer = $this->createUser(['access user profiles']);
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");

    $this->assertFieldAccess(
      $test_user,
      'mail',
      'view',
      $viewer,
      AccessResult::neutral()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['user', 'user.permissions'])
        ->addCacheTags(['config:social_profile.settings'])
    );
  }

  /**
   * Test that a user with the right permissions can view user emails.
   *
   * @see \Drupal\Tests\social_user\Kernel\UserAccessCheck::testCanViewOtherEmailWithAdministerPermission
   */
  public function testCanViewOtherEmailWithAdministerPermission() : void {
    $test_user = $this->createUser();
    $viewer = $this->createUser(['access user profiles', 'administer users']);
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");

    $this->assertFieldAccess(
      $test_user,
      'mail',
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['user.permissions'])
        ->addCacheTags(['config:social_profile.settings'])
    );
  }

}

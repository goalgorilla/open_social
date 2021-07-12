<?php

namespace Drupal\Tests\social_user\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\social_user\Traits\EntityAccessAssertionTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * Test access checks for the user entity.
 *
 * @package social_user
 */
class UserAccessCheck extends KernelTestBase {

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
   * Test that a user doesn't need permission to create a new user.
   */
  public function testCanCreateUserWithoutPermission() : void {
    $viewer = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    $this->assertEntityCreateAccess(
      'user',
      NULL,
      $viewer,
      [],
      AccessResult::allowed()->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test that a user can not be loaded by a permissionless user.
   */
  public function testCannotViewUserWithoutPermission() : void {
    $test_user = $this->createUser();
    $viewer = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");

    $this->assertEntityAccess(
      $test_user,
      'view',
      $viewer,
      AccessResult::neutral()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test that users with the right permission can view users.
   */
  public function testCanViewUserWithPermission() : void {
    $test_user = $this->createUser();
    $viewer = $this->createUser(['access user profiles']);
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");

    $this->assertEntityAccess(
      $test_user,
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheableDependency($test_user)
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test that a user who can view users can't view email.
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
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test that a user who can view users can't view email.
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
        ->addCacheContexts(['user'])
    );
  }

  /**
   * Test that a user with the right permissions can view user emails.
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
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test that a user without permission can only see themselves as user list.
   */
  public function testCanNotListUsersWithoutPermission() : void {
    $this->createUser();
    $this->setUpCurrentUser();

    /** @var \Drupal\user\UserStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $result = $storage->getQuery()->accessCheck(TRUE)->condition('uid', 0, '!=')->execute();

    static::assertEquals([], $result);
  }

  /**
   * Test that a user entity query is allowed given the right permission.
   */
  public function testCanListUsersWithPermission() : void {
    $test_user = $this->createUser();
    $viewer = $this->setUpCurrentUser([], ['access user profiles']);

    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");

    /** @var \Drupal\user\UserStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $result = $storage->getQuery()->accessCheck(TRUE)->condition('uid', 0, '!=')->execute();

    static::assertEquals(
      [
        $test_user->id() => $test_user->id(),
        $viewer->id() => $viewer->id(),
      ],
      $result
    );
  }

}

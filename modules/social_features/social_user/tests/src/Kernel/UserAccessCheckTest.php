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
class UserAccessCheckTest extends KernelTestBase {

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
    $viewer = $this->setUpCurrentUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    $this->assertEntityCreateAccess(
      'user',
      NULL,
      $viewer,
      [],
      AccessResult::neutral()
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test that a user can not be loaded by a permissionless user.
   */
  public function testCannotViewUserWithoutPermission() : void {
    $viewer = $this->setUpCurrentUser();
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

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
    $viewer = $this->setUpCurrentUser([], ['access user profiles']);
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

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
    $user = $this->setUpCurrentUser([], ['access user profiles']);
    self::assertInstanceOf(UserInterface::class, $user, "Test set-up failed: could not create user.");

    $this->assertFieldAccess(
      $user,
      'mail',
      'view',
      $user,
      AccessResult::allowed()
        ->cachePerUser()
    );
  }

  /**
   * Test that a user without permission can only see themselves as user list.
   */
  public function testCanNotListUsersWithoutPermission() : void {
    $this->setUpCurrentUser();
    $this->createUser();

    /** @var \Drupal\user\UserStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $result = $storage->getQuery()->accessCheck(TRUE)->condition('uid', 0, '!=')->execute();

    static::assertEquals([], $result);
  }

  /**
   * Test that a user entity query is allowed given the right permission.
   */
  public function testCanListUsersWithPermission() : void {
    $viewer = $this->setUpCurrentUser([], ['access user profiles']);
    $test_user = $this->createUser();

    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");

    /** @var \Drupal\user\UserStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $result = $storage->getQuery()->accessCheck(TRUE)
      ->condition('uid', [0, 1], 'NOT IN')
      ->execute();

    static::assertEquals(
      [
        $test_user->id() => $test_user->id(),
        $viewer->id() => $viewer->id(),
      ],
      $result
    );
  }

}

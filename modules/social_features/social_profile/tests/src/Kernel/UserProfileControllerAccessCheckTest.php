<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\Entity\ProfileType;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\social_profile\Controller\UserProfileController;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * Test the access check for the UserProfileController.
 */
class UserProfileControllerAccessCheckTest extends ProfileKernelTestBase {

  use UserCreationTrait;

  /**
   * Forbid viewing profiles of blocked users without the right permission.
   */
  public function testForbidsBlockedUserProfileWithoutPermission() : void {
    $viewer = $this->setUpCurrentUser([], ["access user profiles", "view any profile profile"]);
    $test_user = $this->createUser();

    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $test_user->block()->save();

    /** @var \Drupal\profile\Entity\ProfileTypeInterface|NULL $profile_type */
    $profile_type = ProfileType::load('profile');
    self::assertInstanceOf(ProfileTypeInterface::class, $profile_type, "Could not load 'profile' profile type.");

    /** @var callable $controller_method */
    $controller_method = $this->container->get('controller_resolver')->getControllerFromDefinition(UserProfileController::class . "::checkAccess");

    $result = call_user_func($controller_method, $test_user, $profile_type, $viewer);
    $expected = AccessResult::neutral()
      ->cachePerPermissions()
      ->addCacheableDependency($test_user);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Allow viewing profiles of blocked users with the right permission.
   */
  public function testAllowsBlockedUserProfileWithPermission() : void {
    $viewer = $this->setUpCurrentUser([], ["access user profiles", "view any profile profile", "view blocked user"]);
    $test_user = $this->createUser();

    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    $test_user->block()->save();

    /** @var \Drupal\profile\Entity\ProfileTypeInterface|NULL $profile_type */
    $profile_type = ProfileType::load('profile');
    self::assertInstanceOf(ProfileTypeInterface::class, $profile_type, "Could not load 'profile' profile type.");

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get("entity_type.manager")->getStorage("profile");
    $profile = $profile_storage->loadByUser($test_user, "profile");

    self::assertInstanceOf(ProfileInterface::class, $profile, "Profile was not automatically created for user.");

    /** @var callable $controller_method */
    $controller_method = $this->container->get('controller_resolver')->getControllerFromDefinition(UserProfileController::class . "::checkAccess");

    $result = call_user_func($controller_method, $test_user, $profile_type, $viewer);
    $expected = AccessResult::allowed()
      ->cachePerPermissions()
      ->addCacheableDependency($profile_type)
      ->addCacheableDependency($profile);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Test that users who may view profiles can get a not found exception.
   *
   * The exception is thrown in the controller which only happens if the user
   * has access.
   */
  public function testAllowsAccessForProfileNotFoundWithPermission() : void {
    $viewer = $this->setUpCurrentUser([], ["access user profiles", "view any profile profile"]);
    $test_user = $this->createUser();

    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    /** @var \Drupal\profile\Entity\ProfileTypeInterface|NULL $profile_type */
    $profile_type = ProfileType::load('profile');
    self::assertInstanceOf(ProfileTypeInterface::class, $profile_type, "Could not load 'profile' profile type.");

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get("entity_type.manager")->getStorage("profile");
    $profile = $profile_storage->loadByUser($test_user, "profile");
    if ($profile !== NULL) {
      $profile->delete();
    }

    /** @var callable $controller_method */
    $controller_method = $this->container->get('controller_resolver')->getControllerFromDefinition(UserProfileController::class . "::checkAccess");

    $result = call_user_func($controller_method, $test_user, $profile_type, $viewer);
    $expected = AccessResult::allowed()
      ->cachePerPermissions()
      ->addCacheableDependency($profile_type);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Test that access denied shows when users are not allowed to view profiles.
   *
   * Only users who are allowed to view profiles should be able to figure out
   * that a profile doesn't exist.
   */
  public function testPrefersAccessDeniedOverProfileNotFound() : void {
    $viewer = $this->setUpCurrentUser([], ["access user profiles"]);
    $test_user = $this->createUser();

    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");

    /** @var \Drupal\profile\Entity\ProfileTypeInterface|NULL $profile_type */
    $profile_type = ProfileType::load('profile');
    self::assertInstanceOf(ProfileTypeInterface::class, $profile_type, "Could not load 'profile' profile type.");

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get("entity_type.manager")->getStorage("profile");
    $profile = $profile_storage->loadByUser($test_user, 'profile');
    if ($profile !== NULL) {
      $profile->delete();
    }

    /** @var callable $controller_method */
    $controller_method = $this->container->get('controller_resolver')->getControllerFromDefinition(UserProfileController::class . "::checkAccess");

    $result = call_user_func($controller_method, $test_user, $profile_type, $viewer);
    $expected = AccessResult::neutral()
      ->cachePerPermissions()
      ->cachePerUser();

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Assert a certain set of result metadata on a query result.
   *
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $expected
   *   The expected metadata object.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $result
   *   The access result object.
   */
  private function assertAccessMetadata(RefinableCacheableDependencyInterface $expected, RefinableCacheableDependencyInterface $result): void {
    static::assertEquals($expected->getCacheMaxAge(), $result->getCacheMaxAge(), 'Unexpected cache max age.');

    $missingContexts = array_diff($expected->getCacheContexts(), $result->getCacheContexts());
    static::assertEmpty($missingContexts, 'Missing cache contexts: ' . implode(', ', $missingContexts));

    $unexpectedContexts = array_diff($result->getCacheContexts(), $expected->getCacheContexts());
    static::assertEmpty($unexpectedContexts, 'Unexpected cache contexts: ' . implode(', ', $unexpectedContexts));

    $missingTags = array_diff($expected->getCacheTags(), $result->getCacheTags());
    static::assertEmpty($missingTags, 'Missing cache tags: ' . implode(', ', $missingTags));

    $unexpectedTags = array_diff($result->getCacheTags(), $expected->getCacheTags());
    static::assertEmpty($unexpectedTags, 'Unexpected cache tags: ' . implode(', ', $unexpectedTags));
  }

}

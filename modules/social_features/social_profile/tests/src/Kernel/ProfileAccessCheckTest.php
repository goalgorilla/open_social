<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\profile\Entity\ProfileType;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\profile\ProfileTestTrait;
use Drupal\social_profile\FieldManager;
use Drupal\user\UserInterface;
use Drupal\Tests\social_user\Traits\EntityAccessAssertionTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests for the profile access handling.
 *
 * This does not test the access controls around the profile entity itself since
 * that's the responsibility of the profile module and we make no changes. Open
 * Social controls access for the profile entity by assigning permissions but
 * testing that that is done correctly is out of scope for kernel tests.
 */
class ProfileAccessCheckTest extends ProfileKernelTestBase {

  use UserCreationTrait;
  use EntityAccessAssertionTrait;
  use ProfileTestTrait;

  /**
   * Open Social's profile profile type.
   */
  private ProfileTypeInterface $defaultProfileType;

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $profileProfileType = ProfileType::load('profile');
    self::assertInstanceOf(ProfileTypeInterface::class, $profileProfileType);
    $this->defaultProfileType = $profileProfileType;
  }

  /**
   * Test that unmanaged fields do not pose additional access checks.
   */
  public function testUnmanagedFieldAccess() : void {
    $test_field = $this->createTestProfileField();
    $field_storage = $test_field->getFieldStorageDefinition();
    assert($field_storage instanceof FieldStorageConfigInterface);
    $field_storage
      ->setThirdPartySetting("social_profile", "managed_access", FALSE)
      ->save();

    $test_user = $this->setUpCurrentUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    // User can view their own field.
    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $test_user,
      AccessResult::allowed()
    );

    // User can edit their own field.
    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'edit',
      $test_user,
      AccessResult::allowed()
    );
  }

  /**
   * Test that a user can see their own profile fields with the correct perm.
   */
  public function testUserCanViewOwnProfileField() : void {
    $test_field = $this->createTestProfileField();

    $test_user = $this->setUpCurrentUser([], []);
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $test_user,
      AccessResult::allowed()
    );
  }

  /**
   * Test that a user can not edit their own profile field.
   */
  public function testUserCannotEditOwnProfileField() : void {
    $test_field = $this->createTestProfileField();

    $test_user = $this->setUpCurrentUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'edit',
      $test_user,
      AccessResult::forbidden()
        ->addCacheContexts(['user.permissions'])
        ->addCacheableDependency($profile)
    );
  }

  /**
   * Test that a user can edit their own profile field with permissions.
   */
  public function testUserCanEditOwnProfileFieldWithPermission() : void {
    $test_field = $this->createTestProfileField();

    $test_user = $this->setUpCurrentUser([], ["edit own {$test_field->getName()} profile profile field"]);
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'edit',
      $test_user,
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
        ->addCacheableDependency($profile)
    );
  }

  /**
   * Test the visibility of public profile fields as configured by the user.
   */
  public function testPublicProfileFieldConfiguredVisibility() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $viewer = $this->setUpCurrentUser();
    $test_user = $this->createUser(["edit own {$visibility_field->getName()} profile profile field"]);
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $profile
      ->get($visibility_field->getName())
      ->set(0, SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC);
    $profile->save();

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
        ->addCacheableDependency($profile)
    );
  }

  /**
   * Test the visibility of community profile fields as configured by the user.
   */
  public function testCommunityProfileFieldConfiguredVisibility() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $viewer = $this->setUpCurrentUser([], ["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile profile fields"]);
    $test_user = $this->createUser(["edit own {$visibility_field->getName()} profile profile field"]);
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $profile
      ->get($visibility_field->getName())
      ->set(0, SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY);
    $profile->save();

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
        ->addCacheableDependency($profile)
    );
  }

  /**
   * Test the visibility of private profile fields as configured by the user.
   */
  public function testPrivateProfileFieldConfiguredVisibility() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $viewer = $this->setUpCurrentUser([], ["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$test_field->getName()} profile profile fields"]);
    $test_user = $this->createUser(["edit own {$visibility_field->getName()} profile profile field"]);
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $profile
      ->get($visibility_field->getName())
      ->set(0, SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE);
    $profile->save();

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
        ->addCacheableDependency($profile)
    );
  }

  /**
   * Test the visibility of public profile fields as configured by the SM.
   *
   * Visibility is enforced by the SM if the profile owner does not have the
   * permission to change the visibility.
   */
  public function testPublicProfileFieldEnforcedVisibility() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $visibility_field
      ->setDefaultValue(SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC)
      ->save();

    $viewer = $this->setUpCurrentUser();
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test the visibility of community profile fields as configured by the SM.
   *
   * Visibility is enforced by the SM if the profile owner does not have the
   * permission to change the visibility.
   */
  public function testCommunityProfileFieldEnforcedVisibility() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $visibility_field
      ->setDefaultValue(SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY)
      ->save();

    $viewer = $this->setUpCurrentUser([], ["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile profile fields"]);
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test the visibility of private profile fields as configured by the SM.
   *
   * Visibility is enforced by the SM if the profile owner does not have the
   * permission to change the visibility.
   */
  public function testPrivateProfileFieldEnforcedVisibility() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $visibility_field
      ->setDefaultValue(SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE)
      ->save();

    $viewer = $this->setUpCurrentUser([], ["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$test_field->getName()} profile profile fields"]);
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $viewer,
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Test that a user can't edit their profile visibility without permission.
   */
  public function testCannotEditVisibilityWithoutPermission() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $test_user = $this->setUpCurrentUser();
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $visibility_field->getName(),
      'edit',
      $test_user,
      AccessResult::forbidden()
        ->addCacheContexts(['user.permissions'])
        ->addCacheableDependency($profile)
    );
  }

  /**
   * Test that a user can edit their profile visibility if they have permission.
   */
  public function testCanEditVisibilityWithPermission() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $test_user = $this->setUpCurrentUser([], ["edit own {$visibility_field->getName()} profile profile field"]);
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $visibility_field->getName(),
      'edit',
      $test_user,
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
        ->addCacheableDependency($profile)
    );
  }

  /**
   * Test that permissions stay in their lane.
   *
   * We made the conscious decision that having the permission to see private
   * fields does not imply being able to see other fields. This means that
   * visibility is not really a hierarchy but more like categories. The idea is
   * that we can always assign more permissions to a role to ensure they can see
   * both private and community fields, but we can't take away a permission if
   * the private permission would expose community fields.
   */
  public function testPrivatePermissionDoesNotImplyCommunity() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $visibility_field
      ->setDefaultValue(SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY)
      ->save();

    $viewer = $this->setUpCurrentUser([], ["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$test_field->getName()} profile profile fields"]);
    $test_user = $this->createUser();
    self::assertInstanceOf(UserInterface::class, $viewer, "Test set-up failed: could not create user.");
    self::assertInstanceOf(UserInterface::class, $test_user, "Test set-up failed: could not create user.");
    $profile = $this->createProfile($this->defaultProfileType, $test_user);

    $this->assertFieldAccess(
      $profile,
      $test_field->getName(),
      'view',
      $viewer,
      AccessResult::forbidden()
        ->addCacheContexts(['user.permissions'])
    );
  }

  /**
   * Create a field on the profile profile type.
   *
   * Creates a field that can be used for testing. This allows test cases to be
   * independent of the fields included in social_profile.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The created field.
   */
  private function createTestProfileField() : FieldConfigInterface {
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'profile',
      'type' => 'string',
    ]);
    $fieldStorage->save();

    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => $this->defaultProfileType->id(),
    ]);
    $field->save();

    return $field;
  }

  /**
   * Get the visibility field config related to this value field.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $fieldConfig
   *   The value field to load the visibility field for.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The visibility field config.
   */
  private function getVisibilityField(FieldConfigInterface $fieldConfig) : FieldConfigInterface {
    $fieldName = FieldManager::getVisibilityFieldName($fieldConfig);
    self::assertNotNull($fieldName, "Could not get visibility field name for field config.");

    $bundle = $fieldConfig->getTargetBundle();
    self::assertNotNull($bundle, "Field config is not attached to bundle of entity.");

    $visibilityField = FieldConfig::loadByName($fieldConfig->getTargetEntityTypeId(), $bundle, $fieldName);
    self::assertNotNull($visibilityField, "Could not load visibility field for field config.");

    return $visibilityField;
  }

}

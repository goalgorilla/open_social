<?php
/**
 * Created by PhpStorm.
 * User: mglaman
 * Date: 1/15/16
 * Time: 9:41 AM
 */

namespace Drupal\profile\Tests;


/**
 * Tests basic CRUD functionality of profiles.
 *
 * @group profile
 */
use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;

class ProfileDefaultTest extends ProfileTestBase {
  /**
   * Testing demo user 1.
   *
   * @var \Drupal\user\UserInterface
   */
  public $user1;

  /**
   * Testing demo user 2.
   *
   * @var \Drupal\user\UserInterface;
   */
  public $user2;

  /**
   * Profile entity storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  public $profileStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access user profiles',
      'administer profiles',
      'administer profile types',
      'bypass profile access',
      'access administration pages',
    ]);

    $this->user1 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->user1->save();
    $this->user2 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->user2->save();
  }

  /**
   * Tests profiles are active by default.
   */
  public function testProfileActive() {
    $profile_type = $this->createProfileType('test_defaults', 'test_defaults');

    // Create new profiles.
    $profile1 = Profile::create($expected = [
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->save();

    $this->assertTrue($profile1->isActive());

    $profile1->setActive(PROFILE_NOT_ACTIVE);
    $profile1->save();

    $this->assertFalse($profile1->isActive());
  }

  /**
   * Tests default profile functionality.
   */
  public function testDefaultProfile() {
    $profile_type = $this->createProfileType('test_defaults', 'test_defaults');

    // Create new profiles.
    $profile1 = Profile::create($expected = [
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->save();
    $profile2 = Profile::create($expected = [
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile2->setDefault(TRUE);
    $profile2->save();

    $this->assertFalse($profile1->isDefault());
    $this->assertTrue($profile2->isDefault());

    $profile1->setDefault(TRUE)->save();

    $this->assertFalse(Profile::load($profile2->id())->isDefault());
    $this->assertTrue(Profile::load($profile1->id())->isDefault());
  }

  /**
   * Tests loading default from storage handler.
   */
  public function testLoadDefaultProfile() {
    $profile_type = $this->createProfileType('test_defaults', 'test_defaults');

    // Create new profiles.
    $profile1 = Profile::create($expected = [
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->setActive(TRUE);
    $profile1->save();
    $profile2 = Profile::create($expected = [
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile2->setActive(TRUE);
    $profile2->setDefault(TRUE);
    $profile2->save();

    /** @var \Drupal\profile\ProfileStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('profile');

    $default_profile = $storage->loadDefaultByUser($this->user1, $profile_type->id());
    $this->assertEqual($profile2->id(), $default_profile->id());
  }

  /**
   * Tests mark as default action.
   */
  public function testDefaultAction() {
    $types_data = [
      'profile_type_0' => [
        'label' => $this->randomMachineName(),
        'multiple' => TRUE,
      ],
      'profile_type_1' => [
        'label' => $this->randomMachineName(),
        'multiple' => TRUE,
      ],
    ];

    /** @var ProfileType[] $types */
    $types = [];
    foreach ($types_data as $id => $values) {
      $types[$id] = $this->createProfileType($id, $values['label']);
    }

    $restricted_user = $this->drupalCreateUser([
      'administer profiles',
      'edit own ' . $types['profile_type_0']->id() . ' profile',
      'edit own ' . $types['profile_type_1']->id() . ' profile',
    ]);

    $admin_user = $this->drupalCreateUser([
      'administer profiles',
      'edit any ' . $types['profile_type_0']->id() . ' profile',
      'edit any ' . $types['profile_type_1']->id() . ' profile',
    ]);

    // Create new profiles.
    $profile_profile_type_0_restricted_user = Profile::create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $restricted_user->id(),
    ]);
    $profile_profile_type_0_restricted_user->setActive(TRUE);
    $profile_profile_type_0_restricted_user->save();
    $profile_profile_type_0_user1_1 = Profile::create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile_profile_type_0_user1_1->setActive(TRUE);
    $profile_profile_type_0_user1_1->save();
    $profile_profile_type_0_user1_2 = Profile::create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile_profile_type_0_user1_2->setActive(TRUE);
    $profile_profile_type_0_user1_2->save();
    $profile_profile_type_1_user1 = Profile::create($expected = [
      'type' => $types['profile_type_1']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile_profile_type_1_user1->setActive(TRUE);
    $profile_profile_type_1_user1->save();
    $profile_profile_type_1_user2 = Profile::create($expected = [
      'type' => $types['profile_type_1']->id(),
      'uid' => $this->user2->id(),
    ]);
    $profile_profile_type_1_user2->setActive(TRUE);
    $profile_profile_type_1_user2->save();
    $profile_profile_type_1_user1_inactive = Profile::create($expected = [
      'type' => $types['profile_type_1']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile_profile_type_1_user1_inactive->setActive(FALSE);
    $profile_profile_type_1_user1_inactive->save();
    $profile_profile_type_1_user1_active = Profile::create($expected = [
      'type' => $types['profile_type_1']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile_profile_type_1_user1_active->isActive(TRUE);
    $profile_profile_type_1_user1_active->save();

    // Make sure that $restricted_user is allowed to set default his own profile
    // and not others'.
    $this->drupalLogin($restricted_user);

    $this->drupalGet('admin/config/people/profiles');

    $this->clickLink('Mark as default', 0);
    $this->assertTrue(Profile::load($profile_profile_type_0_restricted_user->id())->isDefault());
    $this->clickLink('Mark as default', 1);
    $this->assertResponse(403);
    $this->drupalLogout();

    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/people/profiles');

    // Mark $profile_profile_type_0_user1_1 as default
    // $profile_profile_type_0_user1_2 should stay not default.
    $this->clickLink('Mark as default', 0);
    $this->assertTrue(Profile::load($profile_profile_type_0_user1_1->id())->isDefault());
    $this->assertFalse($profile_profile_type_0_user1_2->isDefault());

    // Mark $profile_profile_type_0_user1_2 as default
    // $profile_profile_type_0_user1_1 should become not default.
    $profile_profile_type_0_user1_2->setDefault(TRUE);
    $profile_profile_type_0_user1_2->save();
    $this->assertTrue($profile_profile_type_0_user1_2->isDefault());
    $this->assertFalse($profile_profile_type_0_user1_1->isDefault());

    // Mark $profile_profile_type_1_user1 as default
    // $profile_profile_type_1_user2 should stay not default.
    $this->clickLink('Mark as default', 1);
    $this->assertTrue(Profile::load($profile_profile_type_1_user1->id())->isDefault());
    $this->assertFalse($profile_profile_type_1_user2->isDefault());

    // Mark $profile_profile_type_1_user2 as default
    // $profile_profile_type_1_user1 should stay default.
    $profile_profile_type_1_user2->setDefault(TRUE);
    $profile_profile_type_1_user2->save();
    $this->assertTrue($profile_profile_type_1_user2->isDefault());
    $this->assertTrue(Profile::load($profile_profile_type_1_user1->id())->isDefault());

    // Mark $profile_profile_type_1_user1_inactive as default
    // $profile_profile_type_1_user1_active should stay not default.
    $this->clickLink('Mark as default', 2);
    $this->assertTrue(Profile::load($profile_profile_type_1_user1_inactive->id())->isDefault());
    $this->assertFalse($profile_profile_type_1_user1_active->isDefault());

    // Mark $profile_profile_type_1_user1_active as default
    // $profile_profile_type_1_user1_inactive should stay default.
    $profile_profile_type_1_user1_active->setDefault(TRUE);
    $profile_profile_type_1_user1_active->save();
    $this->assertTrue($profile_profile_type_1_user1_active->isDefault());
    $this->assertTrue(Profile::load($profile_profile_type_1_user1_inactive->id())->isDefault());
  }

  /**
   * Tests whether profile default on edit is working.
   */
  public function testProfileEdit() {
    $types_data = [
      'profile_type_0' => [
        'label' => $this->randomMachineName(),
        'multiple' => TRUE,
      ],
    ];

    /** @var \Drupal\profile\Entity\ProfileTypeInterface[] $types */
    $types = [];
    foreach ($types_data as $id => $values) {
      $types[$id] = $this->createProfileType($id, $values['label']);
    }

    $admin_user = $this->drupalCreateUser([
      'administer profiles',
      'administer users',
      'edit any ' . $types['profile_type_0']->id() . ' profile',
    ]);

    // Create new profiles.
    $profile1 = Profile::create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->save();
    $profile2 = Profile::create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile2->setDefault(TRUE);
    $profile2->save();

    $this->assertFalse($profile1->isDefault());
    $this->assertTrue($profile2->isDefault());

    $this->drupalLogin($admin_user);

    $this->drupalPostForm("profile/{$profile1->id()}/edit", [], 'Save and make default');

    \Drupal::entityTypeManager()->getStorage('profile')->resetCache([$profile1->id(), $profile2->id()]);
    $this->assertTrue(Profile::load($profile1->id())->isDefault());
    $this->assertFalse(Profile::load($profile2->id())->isDefault());
  }

}

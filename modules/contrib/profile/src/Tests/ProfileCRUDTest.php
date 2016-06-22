<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileCRUDTest.
 */

namespace Drupal\profile\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\user\Entity\User;

/**
 * Tests basic CRUD functionality of profiles.
 *
 * @group profile
 */
class ProfileCRUDTest extends ProfileTestBase {

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
      'access administration pages'
    ]);

    $this->user1 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->user1->save();
    $this->user1->save();
    $this->user2 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->user2->save();
  }

  /**
   * Tests CRUD operations.
   */
  public function testCRUD() {
    $types_data = [
      'profile_type_0' => ['label' => $this->randomMachineName()],
      'profile_type_1' => ['label' => $this->randomMachineName()],
    ];

    /** @var ProfileType[] $types */
    $types = [];
    foreach ($types_data as $id => $values) {
      $types[$id] = ProfileType::create(['id' => $id] + $values);
      $types[$id]->save();
    }

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

    $this->profileStorage = \Drupal::entityTypeManager()->getStorage('profile');

    // Create a new profile.
    $profile = Profile::create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);

    $this->assertIdentical($profile->id(), NULL);
    $this->assertTrue($profile->uuid());
    $this->assertIdentical($profile->getType(), $expected['type']);

    $expected_label = t('@type profile of @username (uid: @uid)',
      [
        '@type' => $types['profile_type_0']->label(),
        '@username' => $this->user1->getDisplayName(),
        '@uid' => $this->user1->id(),
      ]);

    $this->assertEqual($profile->label(), $expected_label,
      new FormattableMarkup('Expected "%expected" but got "%got"', [
        '%expected' => $expected_label,
        '%got' => $profile->label(),
      ])
    );
    $this->assertIdentical($profile->getOwnerId(), $this->user1->id());
    $this->assertIdentical($profile->getCreatedTime(), REQUEST_TIME);
    $this->assertIdentical($profile->getChangedTime(), REQUEST_TIME);

    // Save the profile.
    $status = $profile->save();
    $this->assertIdentical($status, SAVED_NEW);
    $this->assertTrue($profile->id());
    $this->assertIdentical($profile->getChangedTime(), REQUEST_TIME);

    // List profiles for the user and verify that the new profile appears.
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user1->id()]);
    $list_ids = array_keys($list);
    $this->assertEqual($list_ids, [(int) $profile->id()]);

    // Reload and update the profile.
    /** @var Profile $profile */
    $profile = Profile::load($profile->id());
    $profile->setChangedTime($profile->getChangedTime() - 1000);
    $original = clone $profile;
    $status = $profile->save();

    $this->assertIdentical($status, SAVED_UPDATED);
    $this->assertIdentical($profile->id(), $original->id());
    $this->assertEqual($profile->getCreatedTime(), REQUEST_TIME);
    $this->assertEqual($original->getChangedTime(), REQUEST_TIME - 1000);
    // Changed time is only updated when saved through the UI form.
    // @see \Drupal\Core\Entity\ContentEntityForm::submitForm().
    $this->assertEqual($profile->getChangedTime(), REQUEST_TIME - 1000);

    // Create a second profile.
    $user1_profile1 = $profile;
    $profile = Profile::create([
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $status = $profile->save();
    $this->assertIdentical($status, SAVED_NEW);
    $user1_profile = $profile;

    // List profiles for the user and verify that both profiles appear.
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user1->id()]);
    $list_ids = array_keys($list);
    $this->assertEqual($list_ids, [
      (int) $user1_profile1->id(),
      (int) $user1_profile->id(),
    ]);

    // Delete the second profile and verify that the first still exists.
    $user1_profile->delete();
    $this->assertFalse(Profile::load($user1_profile->id()));
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user1->id()]);
    $list_ids = array_keys($list);
    $this->assertEqual($list_ids, [(int) $user1_profile1->id()]);

    // Create a new second profile.
    $user1_profile = Profile::create([
      'type' => $types['profile_type_1']->id(),
      'uid' => $this->user1->id(),
    ]);
    $status = $user1_profile->save();
    $this->assertIdentical($status, SAVED_NEW);

    // Create a profile for the second user.
    $user2_profile1 = Profile::create([
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user2->id(),
    ]);
    $status = $user2_profile1->save();
    $this->assertIdentical($status, SAVED_NEW);

    // Delete the first user and verify that all of its profiles are deleted.
    $this->user1->delete();
    $this->assertFalse(User::load($this->user1->id()));
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user1->id()]);
    $list_ids = array_keys($list);
    $this->assertEqual($list_ids, []);

    // List profiles for the second user and verify that they still exist.
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user2->id()]);
    $list_ids = array_keys($list);
    $this->assertEqual($list_ids, [(int) $user2_profile1->id()]);

    // @todo Rename a profile type; verify that existing profiles are updated.
  }

  /**
   * Tests CRUD operations for profile types through the UI.
   */
  public function testCRUDUI() {
    $types_data = [
      'profile_type_0' => ['label' => $this->randomMachineName()],
      'profile_type_1' => ['label' => $this->randomMachineName()],
    ];

    /** @var ProfileType[] $types */
    $types = [];
    foreach ($types_data as $id => $values) {
      $types[$id] = $this->createProfileType($id, $values['label']);
    }

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

    // Create new profiles.
    $profile1 = Profile::create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->save();
    $profile2 = Profile::create($expected = [
      'type' => $types['profile_type_1']->id(),
      'uid' => $this->user2->id(),
    ]);
    $profile2->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config');
    $this->clickLink('User profiles');
    $this->assertResponse(200);
    $this->assertUrl('admin/config/people/profiles');

    $this->assertLink($profile1->label());
    $this->assertLinkByHref($profile2->toUrl('canonical')->toString());
  }

}

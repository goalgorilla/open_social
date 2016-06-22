<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileViewTest.
 */

namespace Drupal\profile\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewKernelTestBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests profile integration with Views.
 *
 * @group profile
 */
class ProfileViewTest extends ViewKernelTestBase {

  use ProfileTestTrait;

  public static $modules = ['user', 'profile', 'profile_test'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'users',
  ];

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('profile_type');

    ViewTestData::createTestViews(get_class($this), ['profile_test']);
  }

  /**
   * Tests views relationship with multiple referenced entities.
   *
   * Relationship is required, so only users with profiles will be listed.
   */
  public function testProfileRelationship() {
    $profile_type = $this->createProfileType();

    $user[0] = $user1 = User::create([
      'name' => Unicode::strtolower($this->randomMachineName()),
      'status' => TRUE,
    ]);
    $user1->save();
    $user2 = User::create([
      'name' => Unicode::strtolower($this->randomMachineName()),
      'status' => TRUE,
    ]);
    $user2->save();
    $user[1] = $user3 = User::create([
      'name' => Unicode::strtolower($this->randomMachineName()),
      'status' => TRUE,
    ]);
    $user3->save();
    $user4 = User::create([
      'name' => Unicode::strtolower($this->randomMachineName()),
      'status' => TRUE,
    ]);
    $user4->save();

    $profile[0] = $this->createProfile($profile_type, $user1);
    $profile[1] = $this->createProfile($profile_type, $user3);

    Views::viewsData()->clear();

    // Check table relationship exists.
    $views_data = Views::viewsData()->get('users_field_data');
    $this->assertEqual($views_data['profile']['relationship']['base'], 'profile');
    $this->assertEqual($views_data['profile']['relationship']['base field'], 'uid');

    $view = Views::getView('users');
    $this->executeView($view);

    // Ensure values are populated for user and profiles.
    foreach ($view->result as $index => $row) {
      $this->assertEqual($row->uid, $user[$index]->id(), 'User ' . $user[$index]->id() . ' found on row: ' . $index);
      $this->assertEqual($row->profile_users_field_data_profile_id, $profile[$index]->id(), 'Profile ' . $profile[$index]->id() . ' found on view: ' . $index);
    }
  }

  /**
   * Create a user, and optionally a profile.
   *
   * @return \Drupal\user\UserInterface
   *   A newly created user.
   */
  protected function createUser() {
    $user = User::create([
      'name' => Unicode::strtolower($this->randomMachineName()),
      'status' => TRUE,
    ]);
    $user->save();
    return $user;
  }

}

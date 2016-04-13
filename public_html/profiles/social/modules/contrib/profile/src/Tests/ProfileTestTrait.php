<?php

/**
 * @file
 * Contains \Drupal\profile\ProfileTestTrait.
 */

namespace Drupal\profile\Tests;

use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\profile\Entity\ProfileType;
use Drupal\profile\Entity\Profile;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Unicode;

/**
 * Provides methods to create additional profiles and profile_types.
 *
 * This trait is meant to be used only by test classes extending
 * \Drupal\simpletest\TestBase or Drupal\KernelTests\KernelTestBase.
 */
trait ProfileTestTrait {

  /**
   * Creates a profile type for tests.
   *
   * @param string $id
   *   The profile type machine name.
   * @param string $label
   *   The profile type human display name.
   * @param bool|FALSE $registration
   *   Boolean if profile type shows on registration form.
   * @param array $roles
   *   Array of user role machine names.
   *
   * @return \Drupal\profile\Entity\ProfileTypeInterface
   *   Returns a profile type entity.
   */
  protected function createProfileType($id = NULL, $label = NULL, $registration = FALSE, $roles = []) {
    $id = !empty($id) ? $id : $this->randomMachineName();
    $label = !empty($label) ? $label : $this->randomMachineName();

    $type = ProfileType::create([
      'id' => $id,
      'label' => $label,
      'registration' => $registration,
      'roles' => $roles,
    ]);
    $type->save();

    return $type;
  }

  /**
   * Create a user, and optionally a profile.
   *
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   A profile type for the created profile entity.
   * @param \Drupal\user\UserInterface $user
   *   A user to create a profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   A profile for a user.
   */
  protected function createProfile(ProfileTypeInterface $profile_type, UserInterface $user) {
    $profile = Profile::create([
      'type' => $profile_type->id(),
      'uid' => $user->id(),
    ]);
    $profile->save();
    return $profile;
  }

}

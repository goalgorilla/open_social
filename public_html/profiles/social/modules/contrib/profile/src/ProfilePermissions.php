<?php

/**
 * @file
 * Contains \Drupal\profile\ProfilePermissions.
 */

namespace Drupal\profile;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\ProfileType;

/**
 * Defines a class containing permission callbacks.
 */
class ProfilePermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of profile type permissions.
   *
   * @return array
   *    Returns an array of permissions.
   */
  public function profileTypePermissions() {
    $perms = [];
    // Generate profile permissions for all profile types.
    foreach (ProfileType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of permissions for a given profile type.
   *
   * @param \Drupal\profile\Entity\ProfileType $profile_type
   *   The machine name of the profile type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(ProfileType $profile_type) {
    $type_id = $profile_type->id();
    $type_params = ['%type' => $profile_type->label()];

    return [
      "add own $type_id profile" => [
        'title' => $this->t('%type: Add own profile', $type_params),
      ],
      "add any $type_id profile" => [
        'title' => $this->t('%type: Add any profile', $type_params),
      ],
      "view own $type_id profile" => [
        'title' => $this->t('%type: View own profile', $type_params),
      ],
      "view any $type_id profile" => [
        'title' => $this->t('%type: View any profile', $type_params),
      ],
      "edit own $type_id profile" => [
        'title' => $this->t('%type: Edit own profile', $type_params),
      ],
      "edit any $type_id profile" => [
        'title' => $this->t('%type: Edit any profile', $type_params),
      ],
      "delete own $type_id profile" => [
        'title' => $this->t('%type: Delete own profile', $type_params),
      ],
      "delete any $type_id profile" => [
        'title' => $this->t('%type: Delete any profile', $type_params),
      ],
    ];
  }

}

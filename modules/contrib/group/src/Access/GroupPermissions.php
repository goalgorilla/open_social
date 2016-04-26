<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupPermissions.
 */

namespace Drupal\group\Access;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupType;

/**
 * Provides dynamic permissions for groups of different types.
 */
class GroupPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of group type permissions.
   *
   * @return array
   *   The group type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function groupTypePermissions() {
    $perms = [];
    
    // Generate group permissions for all group types.
    foreach (GroupType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of group permissions for a given group type.
   *
   * @param \Drupal\group\Entity\GroupType $type
   *   The group type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(GroupType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id group" => [
        'title' => $this->t('%type_name: Create new group', $type_params),
      ],
    ];
  }

}

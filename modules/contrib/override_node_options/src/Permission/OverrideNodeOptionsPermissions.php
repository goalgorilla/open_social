<?php

/**
 * @file
 * Contains \Drupal\override_node_options\OverrideNodeOptionsPermissions.
 */

namespace Drupal\override_node_options\Permission;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

class OverrideNodeOptionsPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of additional permissions.
   *
   * @return array
   *   An array of permissions.
   */
  public function permissions() {
    $permissions = [];

    /** @var NodeType[] $node_types */
    $node_types = NodeType::loadMultiple();
    foreach ($node_types as $type) {
      $id = $type->id();
      $name = $type->label();

      $permissions["override $id published option"] = [
        'title' => $this->t("Override %type_name published option.", ["%type_name" => $name]),
      ];

      $permissions["override $id promote to front page option"] = [
        'title' => $this->t("Override %type_name promote to front page option.", ["%type_name" => $name]),
      ];

      $permissions["override $id sticky option"] = [
        'title' => $this->t("Override %type_name sticky option.", ["%type_name" => $name]),
      ];

      $permissions["override $id revision option"] = [
        'title' => $this->t("Override %type_name revision option.", ["%type_name" => $name]),
      ];

      $permissions["override $id revision log entry"] = [
        'title' => $this->t("Enter %type_name revision log entry.", ["%type_name" => $name]),
      ];

      $permissions["override $id authored on option"] = [
        'title' => $this->t("Override %type_name authored on option.", ["%type_name" => $name]),
      ];

      $permissions["override $id authored by option"] = [
        'title' => $this->t("Override %type_name authored by option.", ["%type_name" => $name]),
      ];
    }

    return $permissions;
  }

}

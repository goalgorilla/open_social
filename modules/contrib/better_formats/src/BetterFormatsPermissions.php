<?php

/**
 * @file
 * Contains \Drupal\better_formats\BetterFormatPermissions.
 */

namespace Drupal\better_formats;

/**
 * Class BetterFormatsPermissions.
 *
 * @package Drupal\better_formats
 */
class BetterFormatsPermissions {

  /**
   * Returns an array of entity type permissions.
   *
   * Each entity type will show up as a separate row in the permissions UI,
   * allowing controls for the display of format selection per entity type.
   *
   * @return array
   *   The entity type permissions.
   */
  public function permissions() {
    $permissions = [];

    foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        $permissions['show format selection for ' . $entity_type_id] = [
          'title' => t('Show format selection for @entitys', ['@entity' => $entity_type_id]),
        ];
      }
    }
    return $permissions;
  }

}

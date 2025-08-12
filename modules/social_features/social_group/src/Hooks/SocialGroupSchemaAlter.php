<?php

namespace Drupal\social_group\Hooks;

use Drupal\hux\Attribute\Alter;

/**
 * Alters configurations schema.
 */
final class SocialGroupSchemaAlter {

  /**
   * Alters the configuration schema definitions by adding new properties.
   *
   * @param array &$definitions
   *   The array of configuration schema definitions to be altered.
   *
   * @see hook_config_schema_info_alter()
   */
  #[Alter('config_schema_info')]
  public function configSchemaAlter(array &$definitions): void {
    // Add a definition of the group role new property that
    // could be used to display the full label of the role in groups listing,
    // views, options, etc.
    if (isset($definitions['group.role.*'])) {
      $definitions['group.role.*']['mapping']['full_label'] = [
        'type' => 'label',
        'label' => 'Full label',
      ];
    }
  }

}

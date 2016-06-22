<?php

/**
 * @file
 * Contains
 * \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentOptionalType.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\Core\Config\InstallStorage;
use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to the
 * InstallStorage::CONFIG_OPTIONAL_DIRECTORY based on entity types.
 *
 * @Plugin(
 *   id = "optional",
 *   weight = 0,
 *   name = @Translation("Optional type"),
 *   description = @Translation("Assign designated types of configuration to the 'config/optional' install directory. For example, if views are selected as optional, views assigned to any feature will be exported to the 'config/optional' directory and will not create a dependency on the Views module."),
 *   config_route_name = "features.assignment_optional",
 *   default_settings = {
 *     "types" = {
 *       "config" = {},
 *     }
 *   }
 * )
 */
class FeaturesAssignmentOptionalType extends FeaturesAssignmentMethodBase {
  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $this->assignSubdirectoryByConfigTypes(InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
  }

}

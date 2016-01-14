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
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentOptionalType::METHOD_ID,
 *   weight = 0,
 *   name = @Translation("Optional type"),
 *   description = @Translation("Assign designated types of configuration to the 'config/optional' install directory. For example, if views are selected as optional, views assigned to any feature will be exported to the 'config/optional' directory and will not create a dependency on the Views module."),
 *   config_route_name = "features.assignment_optional"
 * )
 */
class FeaturesAssignmentOptionalType extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'optional';

  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $machine_name = 'optional';
    $name = $this->t('Optional');
    $description = $this->t('Provide optional components required by other features.');
    $this->assignSubdirectoryByConfigTypes(self::METHOD_ID, InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
  }

}

<?php

/**
 * @file
 * Contains
 * \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentCoreType.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to a core package based on entity types.
 *
 * @Plugin(
 *   id = "core",
 *   weight = 5,
 *   name = @Translation("Core type"),
 *   description = @Translation("Assign designated types of configuration to a core configuration package module. For example, if image styles are selected as a core type, a core package will be generated and image styles will be assigned to it."),
 *   config_route_name = "features.assignment_core",
 *   default_settings = {
 *     "types" = {
 *       "config" = {},
 *     }
 *   }
 * )
 */
class FeaturesAssignmentCoreType extends FeaturesAssignmentMethodBase {
  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $current_bundle = $this->assigner->getBundle();
    $machine_name = 'core';
    $name = $this->t('Core');
    $description = $this->t('Provides core components required by other features.');
    $this->featuresManager->initPackage($machine_name, $name, $description, 'module', $current_bundle);
    $this->assignPackageByConfigTypes($machine_name, $force);
  }


}

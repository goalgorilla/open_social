<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentDependency.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to packages based on configuration
 * dependencies.
 *
 * @Plugin(
 *   id = "dependency",
 *   weight = 15,
 *   name = @Translation("Dependency"),
 *   description = @Translation("Add to packages configuration dependent on items already in that package."),
 * )
 */
class FeaturesAssignmentDependency extends FeaturesAssignmentMethodBase {
  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $this->featuresManager->assignConfigDependents();
  }

}

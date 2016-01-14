<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentNamespace.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to packages based on namespaces.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentNamespace::METHOD_ID,
 *   weight = 0,
 *   name = @Translation("Namespace"),
 *   description = @Translation("Add to packages configuration with a machine name containing that package's machine name."),
 * )
 */
class FeaturesAssignmentNamespace extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'namespace';

  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $packages = array_keys($this->featuresManager->getPackages());
    $this->featuresManager->assignConfigByPattern(array_combine($packages, $packages));
  }

}

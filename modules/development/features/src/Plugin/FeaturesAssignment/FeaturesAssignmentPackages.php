<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentPackages.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;
use Drupal\features\FeaturesManagerInterface;

/**
 * Class for assigning existing modules to packages.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentPackages::METHOD_ID,
 *   weight = -20,
 *   name = @Translation("Packages"),
 *   description = @Translation("Detect and add existing package modules."),
 * )
 */
class FeaturesAssignmentPackages extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'packages';

  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $bundle = $this->assigner->getBundle();
    $existing = $this->featuresManager->getFeaturesModules($bundle);
    foreach ($existing as $extension) {
      $package = $this->featuresManager->initPackageFromExtension($extension);
      $info = $package['info'];
      $short_name = $this->assigner->getBundle($package['bundle'])->getShortName($extension->getName());

      if (!empty($info['features']['excluded']) || !empty($info['features']['required'])) {
        // Copy over package excluded settings, if any.
        if (!empty($info['features']['excluded'])) {
          $config_collection = $this->featuresManager->getConfigCollection();
          foreach ($info['features']['excluded'] as $config_name) {
            if (isset($config_collection[$config_name])) {
              $package_excluded = $config_collection[$config_name]->getPackageExcluded();
              $package_excluded[] = $short_name;
              $config_collection[$config_name]->setPackageExcluded($package_excluded);
            }
          }
          $this->featuresManager->setConfigCollection($config_collection);
        }
        // Assign required components, if any.
        if (!empty($info['features']['required'])) {
          $this->featuresManager->assignConfigPackage($short_name, $info['features']['required']);
        }
      }
    }
  }

}

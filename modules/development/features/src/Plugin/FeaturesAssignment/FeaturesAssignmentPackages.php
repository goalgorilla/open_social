<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentPackages.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning existing modules to packages.
 *
 * @Plugin(
 *   id = "packages",
 *   weight = -20,
 *   name = @Translation("Packages"),
 *   description = @Translation("Detect and add existing package modules."),
 * )
 */
class FeaturesAssignmentPackages extends FeaturesAssignmentMethodBase {
  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $bundle = $this->assigner->getBundle();
    $existing = $this->featuresManager->getFeaturesModules();
    foreach ($existing as $extension) {
      $package = $this->featuresManager->initPackageFromExtension($extension);
      $features_info = $package->getFeaturesInfo();
      $short_name = $package->getMachineName();

      // Copy over package excluded settings, if any.
      if (!empty($features_info['excluded'])) {
        $config_collection = $this->featuresManager->getConfigCollection();
        foreach ($features_info['excluded'] as $config_name) {
          if (isset($config_collection[$config_name])) {
            $package_excluded = $config_collection[$config_name]->getPackageExcluded();
            $package_excluded[] = $short_name;
            $config_collection[$config_name]->setPackageExcluded($package_excluded);
          }
        }
        $this->featuresManager->setConfigCollection($config_collection);
      }

      // Assign required components, if any.
      if (isset($features_info['required'])) {
        $config = $features_info['required'];
        if (empty($config) || !is_array($config)) {
          // if required is "true" or empty, add all config as required
          $config = $this->featuresManager->listExtensionConfig($extension);
        }
        $this->featuresManager->assignConfigPackage($short_name, $config);
      }
    }
  }

}

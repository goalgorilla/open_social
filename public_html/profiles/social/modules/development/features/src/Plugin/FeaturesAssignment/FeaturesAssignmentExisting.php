<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentExisting.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\Core\Extension\Extension;
use Drupal\features\FeaturesAssignmentMethodBase;
use Drupal\features\FeaturesManagerInterface;

/**
 * Class for assigning existing modules to packages.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentExisting::METHOD_ID,
 *   weight = 12,
 *   name = @Translation("Existing"),
 *   description = @Translation("Add exported config to existing packages."),
 * )
 */
class FeaturesAssignmentExisting extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'existing';

  /**
   * Calls assignConfigPackage without allowing exceptions to abort us.
   *
   * @param string $machine_name
   *   Machine name of package.
   * @param \Drupal\Core\Extension\Extension $extension
   *   An Extension object.
   */
  protected function safeAssignConfig($machine_name, $extension) {
    $config = $this->featuresManager->listExtensionConfig($extension);
    try {
      $this->featuresManager->assignConfigPackage($machine_name, $config);
    }
    catch (\Exception $exception) {
      \Drupal::logger('features')->error($exception->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $packages = $this->featuresManager->getPackages();

    // Assign config to enabled modules first.
    foreach ($packages as $name => $package) {
      if ($package['status'] === FeaturesManagerInterface::STATUS_ENABLED) {
        $this->safeAssignConfig($package['machine_name'], $package['extension']);
      }
    }
    // Now assign to disabled modules.
    foreach ($packages as $name => $info) {
      if ($package['status'] === FeaturesManagerInterface::STATUS_DISABLED) {
        $this->safeAssignConfig($package['machine_name'], $package['extension']);
      }
    }
  }

}

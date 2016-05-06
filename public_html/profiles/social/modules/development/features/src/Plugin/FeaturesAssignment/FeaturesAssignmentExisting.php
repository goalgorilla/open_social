<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentExisting.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;
use Drupal\features\FeaturesManagerInterface;

/**
 * Class for assigning existing modules to packages.
 *
 * @Plugin(
 *   id = "existing",
 *   weight = 12,
 *   name = @Translation("Existing"),
 *   description = @Translation("Add exported config to existing packages."),
 * )
 */
class FeaturesAssignmentExisting extends FeaturesAssignmentMethodBase {
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

    // Assign config to installed modules first.
    foreach ($packages as $name => $package) {
      // @todo Introduce $package->isInstalled() and / or $package->isUninstalled().
      if ($package->getStatus() === FeaturesManagerInterface::STATUS_INSTALLED) {
        $this->safeAssignConfig($package->getMachineName(), $package->getExtension());
      }
    }
    // Now assign to uninstalled modules.
    foreach ($packages as $name => $package) {
      if ($package->getStatus() === FeaturesManagerInterface::STATUS_UNINSTALLED) {
        $this->safeAssignConfig($package->getMachineName(), $package->getExtension());
      }
    }
  }

}

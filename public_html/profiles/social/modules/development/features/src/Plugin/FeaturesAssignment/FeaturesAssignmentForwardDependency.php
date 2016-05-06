<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentForwardDependency.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\Component\Graph\Graph;
use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to packages based on forward dependencies.
 *
 * @Plugin(
 *   id = "forward_dependency",
 *   weight = 20,
 *   name = @Translation("Forward dependency"),
 *   description = @Translation("Add to packages configuration on which items in the package depend."),
 * )
 */
class FeaturesAssignmentForwardDependency extends FeaturesAssignmentMethodBase {
  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $config_collection = $this->featuresManager->getConfigCollection();
    $ordered = $this->dependencyOrder($config_collection);

    foreach ($ordered as $name) {
      $item = $config_collection[$name];
      if ($item->getPackage()) {
        // Already has a package, not our business.
        continue;
      }

      // Find packages of dependent items.
      $dependent_packages = [];
      foreach ($item->getDependents() as $dependent) {
        if (isset($config_collection[$dependent])) {
          if ($package = $config_collection[$dependent]->getPackage()) {
            $dependent_packages[$package] = $package;
          }
        }
      }

      // If zero or multiple packages, we don't know what to do.
      if (count($dependent_packages) == 1) {
        $package = key($dependent_packages);
        $this->featuresManager->assignConfigPackage($package, [$name]);
      }
    }
  }

  /**
   * Get config items such that each item comes before anything it depends on.
   *
   * @param \Drupal\features\ConfigurationItem[] $config_collection
   *   A collection of configuration items.
   *
   * @return string[]
   *   The names of configuration items, in dependency order.
   */
  protected function dependencyOrder($config_collection) {
    // Populate a graph.
    $graph = [];
    foreach ($config_collection as $config) {
      $graph[$config->getName()] = [];
      foreach ($config->getDependents() as $dependent) {
        $graph[$config->getName()]['edges'][$dependent] = 1;
      }
    }
    $graph_object = new Graph($graph);
    $graph = $graph_object->searchAndSort();

    // Order by inverse weight.
    $weights = array_column($graph, 'weight');
    array_multisort($weights, SORT_DESC, $graph);
    return array_keys($graph);
  }

}

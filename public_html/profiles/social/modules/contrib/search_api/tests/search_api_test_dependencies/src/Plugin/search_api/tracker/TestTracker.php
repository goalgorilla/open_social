<?php

namespace Drupal\search_api_test_dependencies\Plugin\search_api\tracker;

use Drupal\search_api\Plugin\search_api\tracker\Basic as BasicTracker;

/**
 * Provides a tracker with dependencies, for the dependency removal tests.
 *
 * @SearchApiTracker(
 *   id = "search_api_test_dependencies",
 *   label = @Translation("Test tracker"),
 * )
 */
class TestTracker extends BasicTracker {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $remove = \Drupal::state()
      ->get('search_api_test_dependencies.tracker.remove', FALSE);
    if ($remove) {
      $this->configuration = array();
    }
    return $remove;
  }

}

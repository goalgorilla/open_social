<?php

namespace Drupal\search_api_test_dependencies\Plugin\search_api\processor;

use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Provides a processor with dependencies, for the dependency removal tests.
 *
 * @SearchApiProcessor(
 *   id = "search_api_test_dependencies",
 *   label = @Translation("Dependency test processor"),
 * )
 */
class TestProcessor extends ProcessorPluginBase {

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
      ->get('search_api_test_dependencies.processor.remove', FALSE);
    if ($remove) {
      $this->configuration = array();
    }
    return $remove;
  }

}

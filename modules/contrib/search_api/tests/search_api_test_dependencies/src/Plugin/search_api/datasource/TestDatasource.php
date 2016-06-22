<?php

namespace Drupal\search_api_test_dependencies\Plugin\search_api\datasource;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Datasource\DatasourcePluginBase;

/**
 * Provides a datasource with dependencies, for the dependency removal tests.
 *
 * @SearchApiDatasource(
 *   id = "search_api_test_dependencies",
 *   label = @Translation("Dependency test datasource"),
 * )
 */
class TestDatasource extends DatasourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    return NULL;
  }

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
      ->get('search_api_test_dependencies.datasource.remove', FALSE);
    if ($remove) {
      $this->configuration = array();
    }
    return $remove;
  }

}

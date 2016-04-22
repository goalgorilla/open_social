<?php

namespace Drupal\search_api_test_backend\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a dummy data type for testing purposes.
 *
 * @SearchApiDataType(
 *   id = "search_api_altering_test_data_type",
 *   label = @Translation("Altering test data type"),
 *   description = @Translation("Altering dummy data type implementation")
 * )
 */
class AlteringValueTestDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    return strlen($value);
  }

}

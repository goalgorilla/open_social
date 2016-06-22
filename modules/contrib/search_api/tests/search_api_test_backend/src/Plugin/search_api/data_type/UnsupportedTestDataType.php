<?php

namespace Drupal\search_api_test_backend\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a dummy data type for testing purposes.
 *
 * @SearchApiDataType(
 *   id = "search_api_unsupported_test_data_type",
 *   label = @Translation("Unsupported test data type"),
 *   description = @Translation("Unsupported dummy data type implementation")
 * )
 */
class UnsupportedTestDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFallbackType() {
    return 'integer';
  }

}

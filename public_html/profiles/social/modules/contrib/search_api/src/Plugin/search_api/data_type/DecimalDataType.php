<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\search_api\data_type\DecimalDataType.
 */

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a decimal data type.
 *
 * @SearchApiDataType(
 *   id = "decimal",
 *   label = @Translation("Decimal"),
 *   default = "true"
 * )
 */
class DecimalDataType extends DataTypePluginBase {

}

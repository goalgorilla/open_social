<?php

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides an integer data type.
 *
 * @SearchApiDataType(
 *   id = "integer",
 *   label = @Translation("Integer"),
 *   description = @Translation("Contains integer values."),
 *   default = "true"
 * )
 */
class IntegerDataType extends DataTypePluginBase {

}

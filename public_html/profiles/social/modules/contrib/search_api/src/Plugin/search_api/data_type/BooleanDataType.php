<?php

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a boolean data type.
 *
 * @SearchApiDataType(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("Boolean fields can only have one of two values: true or false."),
 *   default = "true"
 * )
 */
class BooleanDataType extends DataTypePluginBase {

}

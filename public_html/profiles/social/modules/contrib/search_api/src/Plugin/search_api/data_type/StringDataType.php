<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\search_api\data_type\StringDataType.
 */

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a string data type.
 *
 * @SearchApiDataType(
 *   id = "string",
 *   label = @Translation("String"),
 *   default = "true"
 * )
 */
class StringDataType extends DataTypePluginBase {

}

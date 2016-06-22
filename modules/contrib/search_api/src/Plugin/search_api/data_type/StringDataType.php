<?php

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a string data type.
 *
 * @SearchApiDataType(
 *   id = "string",
 *   label = @Translation("String"),
 *   description = @Translation("String fields are used for short, keyword-like character strings where you only want to find complete field values, not individual words."),
 *   default = "true"
 * )
 */
class StringDataType extends DataTypePluginBase {

}

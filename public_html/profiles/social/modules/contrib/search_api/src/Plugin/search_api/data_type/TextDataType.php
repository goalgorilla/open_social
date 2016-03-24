<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\search_api\data_type\TextDataType.
 */

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a full text data type.
 *
 * @SearchApiDataType(
 *   id = "text",
 *   label = @Translation("Fulltext"),
 *   default = "true"
 * )
 */
class TextDataType extends DataTypePluginBase {

}

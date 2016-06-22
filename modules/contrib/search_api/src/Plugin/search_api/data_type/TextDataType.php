<?php

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a full text data type.
 *
 * @SearchApiDataType(
 *   id = "text",
 *   label = @Translation("Fulltext"),
 *   description = @Translation("Fulltext fields are analyzed fields which are made available for fulltext search. This data type should be used for any fields (usually with free text input by users) which you want to search for individual words."),
 *   default = "true"
 * )
 */
class TextDataType extends DataTypePluginBase {

}

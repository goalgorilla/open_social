<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\search_api\processor\IgnoreCase.
 */

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Component\Utility\Unicode;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * @SearchApiProcessor(
 *   id = "ignorecase",
 *   label = @Translation("Ignore case"),
 *   description = @Translation("Makes searches case-insensitive on selected fields."),
 *   stages = {
 *     "preprocess_index" = -20,
 *     "preprocess_query" = -20
 *   }
 * )
 */
class IgnoreCase extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // We don't touch integers, NULL values or the like.
    if (is_string($value)) {
      $value = Unicode::strtolower($value);
    }
  }

}

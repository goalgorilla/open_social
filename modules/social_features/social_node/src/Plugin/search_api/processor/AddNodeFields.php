<?php

namespace Drupal\social_node\Plugin\search_api\processor;

use Drupal\social_node\SearchApiAddNodeFieldsProcessorBase;

/**
 * Adds node fields used for controlling node access on query alters.
 *
 * @SearchApiProcessor(
 *   id = "social_node_node_fields",
 *   label = @Translation("Social Node: Add requried node fields to index"),
 *   description = @Translation("Add requried node fields to index."),
 *   stages = {
 *     "pre_index_save" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddNodeFields extends SearchApiAddNodeFieldsProcessorBase {

  /**
   * {@inheritdoc}
   */
  protected function getNodeFieldsName(): array {
    return [
      'status' => ['type' => 'boolean'],
      'uid' => ['type' => 'integer'],
      'type' => ['type' => 'string'],
      'field_content_visibility' => ['type' => 'string'],
    ];
  }

}

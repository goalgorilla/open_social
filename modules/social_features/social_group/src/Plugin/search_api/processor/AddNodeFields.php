<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\search_api\processor;

use Drupal\social_node\SearchApiAddNodeFieldsProcessorBase;

/**
 * Adds node fields used for controlling node access on query alters.
 *
 * @SearchApiProcessor(
 *   id = "social_group_node_fields",
 *   label = @Translation("Social group: Add requried node fields to index"),
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
      'groups' => ['type' => 'integer'],
      'field_content_visibility' => ['type' => 'string'],
    ];
  }

}

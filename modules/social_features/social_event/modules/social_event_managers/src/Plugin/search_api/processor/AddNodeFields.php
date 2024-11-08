<?php

declare(strict_types=1);

namespace Drupal\social_event_managers\Plugin\search_api\processor;

use Drupal\social_node\SearchApiAddNodeFieldsProcessorBase;

/**
 * Adds node fields used for controlling node access on query alters.
 *
 * @SearchApiProcessor(
 *   id = "social_event_managers_node_fields",
 *   label = @Translation("Social Event Managers: Add required node fields to index"),
 *   description = @Translation("Add required node fields to index."),
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
      'field_event_managers' => ['type' => 'integer'],
    ];
  }

}

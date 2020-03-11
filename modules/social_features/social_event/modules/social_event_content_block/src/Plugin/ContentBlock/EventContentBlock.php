<?php

namespace Drupal\social_event_content_block\Plugin\ContentBlock;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\social_content_block\ContentBlockBase;

/**
 * Provides a content block for events.
 *
 * @ContentBlock(
 *   id = "event_content_block",
 *   entityTypeId = "node",
 *   bundle = "event",
 *   fields = {
 *     "field_event_type",
 *     "field_event_group",
 *   },
 * )
 */
class TopicContentBlock extends ContentBlockBase {

  /**
   * {@inheritdoc}
   */
  public function query(SelectInterface $query, array $fields) {
    foreach ($fields as $field_name => $entity_ids) {
      switch ($field_name) {
        case 'field_event_type':
          $query->innerJoin('node__field_event_type', 'et', 'et.entity_id = base_table.nid');
          $query->condition('et.field_event_type_target_id', $entity_ids, 'IN');
          break;

        case 'field_event_group':
          $query->innerJoin('group_content_field_data', 'gc', 'gc.entity_id = base_table.nid');
          $query->condition('gc.type', '%' . $query->escapeLike('-group_node-event'), 'LIKE');
          $query->condition('gc.gid', $entity_ids, 'IN');
          break;
      }
    }
  }

}

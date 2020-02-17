<?php

namespace Drupal\social_content_block\Plugin\ContentBlock;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\social_content_block\ContentBlockBase;

/**
 * Provides a content block for topics.
 *
 * @ContentBlock(
 *   id = "topic_content_block",
 *   entityTypeId = "node",
 *   bundle = "topic",
 *   fields = {
 *     "field_topic_type",
 *     "field_content_tags",
 *     "field_group",
 *   },
 * )
 */
class TopicContentBlock extends ContentBlockBase {

  /**
   * {@inheritdoc}
   */
  public function query(SelectInterface $query, array $fields) {
    foreach ($fields as $field_name => $entity_ids) {
      // If there are no entity ids to limit to we allow them all.
      if (empty($entity_ids)) {
        continue;
      }
      switch ($field_name) {
        // Add topic type tags.
        case 'field_topic_type':
          $query->innerJoin('node__field_topic_type', 'tt', 'tt.entity_id = base_table.nid');
          $query->condition('tt.field_topic_type_target_id', $entity_ids, 'IN');
          break;

        // Add group tags.
        case 'field_group':
          $query->innerJoin('group_content_field_data', 'gd', 'gd.entity_id = base_table.nid');
          $query->condition('gd.gid', $entity_ids, 'IN');
          break;

        case 'field_content_tags':
          $query->innerJoin('node__social_tagging', 'st', 'st.entity_id = base_table.nid');
          $query->condition('st.social_tagging_target_id', $entity_ids, 'IN');
          break;
      }
    }
  }

}

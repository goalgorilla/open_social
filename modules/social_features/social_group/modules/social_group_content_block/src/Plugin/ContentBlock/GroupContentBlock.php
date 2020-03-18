<?php

namespace Drupal\social_group_content_block\Plugin\ContentBlock;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\social_content_block\ContentBlockBase;

/**
 * Provides a content block for groups.
 *
 * @ContentBlock(
 *   id = "group_content_block",
 *   entityTypeId = "group",
 *   fields = {
 *     "field_group_content_tag",
 *   },
 * )
 */
class GroupContentBlock extends ContentBlockBase {

  /**
   * {@inheritdoc}
   */
  public function query(SelectInterface $query, array $fields) {
    foreach ($fields as $field_name => $entity_ids) {
      switch ($field_name) {
        case 'field_group_content_tag':
          $query->innerJoin('group__social_tagging', 'st', 'st.entity_id = base_table.id');
          $query->condition('st.social_tagging_target_id', $entity_ids, 'IN');
          break;
      }
    }
  }

}

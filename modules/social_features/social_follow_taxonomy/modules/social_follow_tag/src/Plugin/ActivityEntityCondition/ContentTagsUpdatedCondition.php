<?php

namespace Drupal\social_follow_tag\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\node\NodeInterface;

/**
 * Notification condition to require a tag addition to content.
 *
 * Requires that the notification is for a node type and will only apply the
 * notification if the action is an update that adds a new content tag to the
 * node.
 *
 * @ActivityEntityCondition(
 *  id = "content_tags_updated",
 *  label = @Translation("Content Tags have been updated"),
 *  entities = {"node" = {}}
 * )
 */
class ContentTagsUpdatedCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   */
  public function isValidEntityCondition($entity) : bool {
    assert(isset($entity->original), __CLASS__ . " should only be used with the entity_update_action.");
    assert($entity instanceof NodeInterface, __CLASS__ . " only works on notifications for nodes.");
    /** @var \Drupal\node\NodeInterface $original_entity */
    $original_entity = $entity->original;

    if (!$entity->hasField("field_social_tagging")) {
      return FALSE;
    }

    $original_values = array_map(
      fn ($item) => $item['target_id'],
      $original_entity->get('field_social_tagging')->getValue(),
    );
    $new_values = array_map(
      fn ($item) => $item['target_id'],
      $entity->get('field_social_tagging')->getValue(),
    );

    // Check if the count of new terms are bigger than 0 (removed terms are
    // ignored).
    return count(array_diff($new_values, $original_values)) > 0;
  }

}

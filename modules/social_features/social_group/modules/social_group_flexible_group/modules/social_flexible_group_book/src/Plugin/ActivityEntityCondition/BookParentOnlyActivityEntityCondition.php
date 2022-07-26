<?php

namespace Drupal\social_flexible_group_book\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\node\NodeInterface;

/**
 * Provides a 'BookParentOnlyActivityEntityCondition' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "book_parent_only",
 *  label = @Translation("Book parent only"),
 *  entities = {"group_content" = {}}
 * )
 */
class BookParentOnlyActivityEntityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity): bool {
    if ($entity->getEntityTypeId() === 'group_content') {
      $node = $entity->getEntity();
    }
    elseif ($entity->getEntityTypeId() === 'node') {
      $node = $entity;
    }

    if (!empty($node) && $node instanceof NodeInterface) {
      // The main book page always has the depth 1.
      if (
        $node->bundle() === 'book' &&
        isset($node->book['depth']) &&
        (int) $node->book['depth'] === 1
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

}

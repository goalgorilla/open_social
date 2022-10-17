<?php

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\comment\CommentInterface;

/**
 * Provides a 'CommentReply' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "comment_all",
 *  label = @Translation("All comments"),
 *  entities = {"comment" = {}}
 * )
 */
class CommentAllActivityEntityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) : bool {
    return $entity instanceof CommentInterface;
  }

}

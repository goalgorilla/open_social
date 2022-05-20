<?php

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\comment\CommentInterface;

/**
 * Provides a 'CommentReply' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "comment_reply",
 *  label = @Translation("Reply comment"),
 *  entities = {"comment" = {}}
 * )
 */
class CommentReplyActivityEntityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) : bool {
    return $entity instanceof CommentInterface && $entity->getParentComment() !== NULL;
  }

}

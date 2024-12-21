<?php

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides a 'CommentNotReply' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "comment_not_reply",
 *  label = @Translation("Not reply comment"),
 *  entities = {"comment" = {}}
 * )
 */
class CommentNotReplyActivityEntityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition(ContentEntityInterface $entity) : bool {
    return $entity instanceof CommentInterface && $entity->getParentComment() === NULL;
  }

}

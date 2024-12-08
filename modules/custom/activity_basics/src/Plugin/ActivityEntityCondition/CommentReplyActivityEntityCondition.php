<?php

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityInterface;

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
  public function isValidEntityCondition(ContentEntityInterface $entity) : bool {
    return $entity instanceof CommentInterface && $entity->getParentComment() !== NULL;
  }

}

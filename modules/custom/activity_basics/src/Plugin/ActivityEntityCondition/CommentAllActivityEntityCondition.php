<?php

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityInterface;

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
  public function isValidEntityCondition(ContentEntityInterface $entity) : bool {
    return $entity instanceof CommentInterface;
  }

}

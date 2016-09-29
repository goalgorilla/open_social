<?php

/**
 * @file
 * Contains \Drupal\activity_basics\Plugin\ActivityEntityCondition\CreateActivityEntityCondition.
 */

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;

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
  public function isValidEntityCondition($entity) {
    if ($entity->getEntityTypeId() === 'comment') {
      if(!empty($entity->getParentComment())){
        return TRUE;
      }
    }
    return FALSE;
  }

}

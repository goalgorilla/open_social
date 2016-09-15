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
 *  label = @Translation("Replies to my comments"),
 *  entities = {"comment" = {}}
 * )
 */
class CommentReplyActivityEntityCondition extends ActivityEntityConditionBase {


}

<?php

/**
 * @file
 * Contains \Drupal\activity_basics\Plugin\ActivityEntityCondition\CreateActivityEntityCondition.
 */

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;

/**
 * Provides a 'CommentPostProfile' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "comment_post_profile",
 *  label = @Translation("Comments on a post on my profile"),
 *  entities = {"comment" = {"post_comment"}}
 * )
 */
class CommentPostProfileActivityEntityCondition extends ActivityEntityConditionBase {


}

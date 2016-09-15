<?php

/**
 * @file
 * Contains \Drupal\activity_basics\Plugin\ActivityEntityCondition\CreateActivityEntityCondition.
 */

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;

/**
 * Provides a 'CommentMyContent' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "comment_my_entity",
 *  label = @Translation("Comments on a content or post created by me"),
 *  entities = {"comment" = {}}
 * )
 */
class CommentMyContentActivityEntityCondition extends ActivityEntityConditionBase {


}

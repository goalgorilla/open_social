<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoComment;

/**
 * @DemoContent(
 *   id = "comment",
 *   label = @Translation("Comment"),
 *   source = "content/entity/comment.yml",
 *   entity_type = "comment"
 * )
 */
class Comment extends DemoComment {

}

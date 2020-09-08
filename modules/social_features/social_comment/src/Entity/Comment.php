<?php

namespace Drupal\social_comment\Entity;

use Drupal\comment\Entity\Comment as CommentBase;
use Drupal\social_core\EntityUrlLanguageTrait;

/**
 * Provides a Comment entity that has links that work with different languages.
 */
class Comment extends CommentBase {
  use EntityUrlLanguageTrait;

}

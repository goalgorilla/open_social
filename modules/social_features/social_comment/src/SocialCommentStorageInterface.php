<?php

namespace Drupal\social_comment;

use Drupal\comment\CommentStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for comment entity storage classes.
 */
interface SocialCommentStorageInterface extends CommentStorageInterface {

  /**
   * Retrieves comments for a thread.
   *
   * @param string $formatter
   *   The comments field formatter identifier.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose comment(s) needs rendering.
   * @param string $field_name
   *   The field_name whose comment(s) needs rendering.
   * @param int $mode
   *   The comment display mode: CommentManagerInterface::COMMENT_MODE_FLAT or
   *   CommentManagerInterface::COMMENT_MODE_THREADED.
   * @param int $comments_per_page
   *   The amount of comments to display per page. Sets to 0, which means show
   *   all comments.
   * @param int $pager_id
   *   Pager id to use in case of multiple pagers on the one page.
   * @param string $order
   *   The direction to sort. Legal values are "ASC" and "DESC". Any other value
   *   will be converted to "ASC".
   * @param int $limit
   *   The number of records to return from the result set.
   *
   * @return array
   *   Ordered array of comment objects, keyed by comment id.
   */
  public function loadFormatterThread(
    string $formatter,
    EntityInterface $entity,
    string $field_name,
    int $mode,
    int $comments_per_page,
    int $pager_id,
    string $order,
    int $limit
  ): array;

}

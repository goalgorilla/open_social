<?php

namespace Drupal\social_comment;

use Drupal\comment\CommentStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\social_comment\Plugin\Field\FieldFormatter\SocialCommentFormatterInterface;

/**
 * Defines an interface for comment entity storage classes.
 */
interface SocialCommentStorageInterface extends CommentStorageInterface {

  /**
   * Retrieves comments for a thread, sorted in an order suitable for display.
   *
   * @param \Drupal\social_comment\Plugin\Field\FieldFormatter\SocialCommentFormatterInterface $formatter
   *   The comments field formatter.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose comment(s) needs rendering.
   * @param string $field_name
   *   The field_name whose comment(s) needs rendering.
   * @param int $mode
   *   The comment display mode: CommentManagerInterface::COMMENT_MODE_FLAT or
   *   CommentManagerInterface::COMMENT_MODE_THREADED.
   * @param int $comments_per_page
   *   (optional) The amount of comments to display per page.
   *   Defaults to 0, which means show all comments.
   * @param int $pager_id
   *   (optional) Pager id to use in case of multiple pagers on the one page.
   *   Defaults to 0; is only used when $comments_per_page is greater than zero.
   * @param array $items
   *   (optional) The items list.
   *
   * @return array
   *   Ordered array of comment objects, keyed by comment id.
   */
  public function loadFormatterThread(
    SocialCommentFormatterInterface $formatter,
    EntityInterface $entity,
    string $field_name,
    int $mode,
    int $comments_per_page = 0,
    int $pager_id = 0,
    array $items = []
  ): array;

}

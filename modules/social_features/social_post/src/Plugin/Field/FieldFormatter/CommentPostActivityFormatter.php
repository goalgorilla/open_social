<?php

namespace Drupal\social_post\Plugin\Field\FieldFormatter;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * Provides a post comment activity formatter.
 *
 * @FieldFormatter(
 *   id = "comment_post_activity",
 *   module = "social_post",
 *   label = @Translation("Last two comments on post"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class CommentPostActivityFormatter extends CommentPostFormatter {

  /**
   * {@inheritdoc}
   */
  public static function alterQuery(
    SelectInterface $query,
    int $limit,
    string $order = 'ASC'
  ): void {
    parent::alterQuery($query, $limit);
  }

}

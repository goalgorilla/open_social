<?php

namespace Drupal\social_comment\Plugin\Field\FieldFormatter;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * Defines an interface for comment field formatter classes.
 *
 * @package Drupal\social_comment\Plugin\Field\FieldFormatter
 */
interface SocialCommentFormatterInterface {

  /**
   * Alters a query for filtering comments.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query.
   * @param int $limit
   *   The number of records to return from the result set.
   * @param string $order
   *   (optional) The direction to sort. Legal values are "ASC" and "DESC". Any
   *   other value will be converted to "ASC". Defaults to 'ASC'.
   */
  public static function alterQuery(
    SelectInterface $query,
    int $limit,
    string $order = 'ASC'
  ): void;

}

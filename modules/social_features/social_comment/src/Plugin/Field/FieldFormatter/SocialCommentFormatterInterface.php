<?php

namespace Drupal\social_comment\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Defines an interface for comment field formatter classes.
 *
 * @package Drupal\social_comment\Plugin\Field\FieldFormatter
 */
interface SocialCommentFormatterInterface extends DerivativeInspectionInterface {

  /**
   * Alters a query for filtering comments.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query.
   * @param array $items
   *   (optional) The items list.
   */
  public static function alterQuery(SelectInterface $query, array $items = []): void;

}

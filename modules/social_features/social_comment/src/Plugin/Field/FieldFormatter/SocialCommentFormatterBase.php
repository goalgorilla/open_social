<?php

namespace Drupal\social_comment\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Base class for comments field formatter.
 *
 * @package Drupal\social_comment\Plugin\Field\FieldFormatter
 */
abstract class SocialCommentFormatterBase extends CommentDefaultFormatter implements SocialCommentFormatterInterface {

  /**
   * The comment storage.
   *
   * @var \Drupal\social_comment\SocialCommentStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static function alterQuery(
    SelectInterface $query,
    int $limit,
    string $order = 'ASC'
  ): void {
    $order_by = &$query->getOrderBy();

    foreach (['c.cid', 'torder'] as $field) {
      if (isset($order_by[$field]) && $order_by[$field] !== $order) {
        $order_by[$field] = $order;
        break;
      }
    }

    if ($limit > 0) {
      $query->range(0, $limit);
    }
  }

}

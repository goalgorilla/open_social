<?php

namespace Drupal\social_book_content_block\Plugin\ContentBlock;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\social_content_block\ContentBlockBase;

/**
 * Provides a content block for books.
 *
 * @ContentBlock(
 *   id = "book_content_block",
 *   entityTypeId = "node",
 *   bundle = "book",
 *   fields = {},
 * )
 */
class BookContentBlock extends ContentBlockBase {

  /**
   * {@inheritdoc}
   */
  public function query(SelectInterface $query, array $fields) {

  }

}

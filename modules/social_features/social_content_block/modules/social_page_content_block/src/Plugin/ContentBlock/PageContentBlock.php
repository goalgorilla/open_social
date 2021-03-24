<?php

namespace Drupal\social_page_content_block\Plugin\ContentBlock;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\social_content_block\ContentBlockBase;

/**
 * Provides a content block for pags.
 *
 * @ContentBlock(
 *   id = "page_content_block",
 *   entityTypeId = "node",
 *   bundle = "page",
 *   fields = {},
 * )
 */
class PageContentBlock extends ContentBlockBase {

  /**
   * {@inheritdoc}
   */
  public function query(SelectInterface $query, array $fields) {

  }

}

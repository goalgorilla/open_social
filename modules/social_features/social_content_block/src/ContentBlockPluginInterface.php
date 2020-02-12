<?php

namespace Drupal\social_content_block;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * Interface ContentBlockPluginInterface.
 *
 * @package Drupal\social_content_block
 */
interface ContentBlockPluginInterface {

  /**
   * Create filtering query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query.
   * @param array $fields
   *   The fields.
   */
  public function query(SelectInterface $query, array $fields);

}

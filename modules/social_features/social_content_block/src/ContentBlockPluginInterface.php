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

  /**
   * The sort options that are supported for this content block type.
   *
   * Used to configure the sorting field storage as well as the content block
   * form.
   *
   * @return array
   *   An array with sorting option's system name as key and a human readable
   *   label as value.
   */
  public function supportedSortOptions() : array;

}

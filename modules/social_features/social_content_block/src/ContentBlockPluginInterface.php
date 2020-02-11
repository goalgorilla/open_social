<?php

namespace Drupal\social_content_block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface ContentBlockPluginInterface.
 *
 * @package Drupal\social_content_block
 */
interface ContentBlockPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * Create filtering query.
   *
   * @param array $fields
   *   The fields.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query.
   */
  public function query(array $fields);

}

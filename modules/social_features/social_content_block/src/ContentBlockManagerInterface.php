<?php

namespace Drupal\social_content_block;

use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;

/**
 * Interface ContentBlockManagerInterface.
 *
 * @package Drupal\social_content_block
 */
interface ContentBlockManagerInterface extends ContextAwarePluginManagerInterface {

  /**
   * Build the States API selector.
   *
   * @param string $field_name
   *   The field name.
   * @param string $column
   *   The field column name.
   * @param array|null $field_parents
   *   (optional) The field parents.
   *
   * @return string
   *   The selector.
   */
  public function getSelector($field_name, $column, $field_parents = NULL);

}

<?php

namespace Drupal\social_content_block;

use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;

/**
 * Defines the content block manager interface.
 *
 * @package Drupal\social_content_block
 */
interface ContentBlockManagerInterface extends ContextAwarePluginManagerInterface {

  /**
   * Creates a pre-configured instance of a plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\social_content_block\ContentBlockPluginInterface
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = []);

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

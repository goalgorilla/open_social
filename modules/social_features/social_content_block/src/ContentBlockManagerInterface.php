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
   * Build the parent elements list.
   *
   * @param string $field_name
   *   The field name.
   * @param string|null $column
   *   (optional) The field column name. Defaults to NULL.
   * @param array|null $element
   *   (optional) The element. Defaults to NULL.
   * @param bool $is_field
   *   (optional) TRUE if the element is a single field. Defaults to FALSE.
   *
   * @return array
   *   The list.
   */
  public function getParents(
    string $field_name,
    string $column = NULL,
    array $element = NULL,
    bool $is_field = FALSE
  );

  /**
   * Build the States API selector.
   *
   * @param string $field_name
   *   The field name.
   * @param string|null $column
   *   (optional) The field column name. Defaults to NULL.
   * @param array|null $element
   *   (optional) The element. Defaults to NULL.
   * @param bool $is_field
   *   (optional) TRUE if the element is a single field. Defaults to FALSE.
   *
   * @return string
   *   The selector.
   */
  public function getSelector(
    string $field_name,
    string $column = NULL,
    array $element = NULL,
    bool $is_field = FALSE
  );

}

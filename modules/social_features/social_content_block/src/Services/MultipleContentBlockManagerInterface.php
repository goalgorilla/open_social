<?php

namespace Drupal\social_content_block\Services;

/**
 * Defines the multiple content block manager.
 *
 * @package Drupal\social_content_block
 */
interface MultipleContentBlockManagerInterface {

  /**
   * Gets the definition of all plugins for this type.
   *
   * @return mixed[]
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   *
   * @see \Drupal\Core\Plugin\FilteredPluginManagerInterface::getFilteredDefinitions()
   */
  public function getDefinitions();

}

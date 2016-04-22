<?php

namespace Drupal\search_api\Plugin;

use Drupal\search_api\IndexInterface;

/**
 * Represents a plugin that is linked to an index.
 */
interface IndexPluginInterface extends ConfigurablePluginInterface {

  /**
   * Retrieves the index this plugin is configured for.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The index this plugin is configured for.
   */
  public function getIndex();

  /**
   * Sets the index this plugin is configured for.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index this plugin is configured for.
   */
  public function setIndex(IndexInterface $index);

}

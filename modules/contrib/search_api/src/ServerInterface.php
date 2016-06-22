<?php

namespace Drupal\search_api;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\search_api\Backend\BackendSpecificInterface;

/**
 * Defines the interface for server entities.
 */
interface ServerInterface extends ConfigEntityInterface, BackendSpecificInterface {

  /**
   * Retrieves the server's description.
   *
   * @return string
   *   The description of the server.
   */
  public function getDescription();

  /**
   * Determines whether the backend is valid.
   *
   * @return bool
   *   TRUE if the backend is valid, FALSE otherwise.
   */
  public function hasValidBackend();

  /**
   * Retrieves the plugin ID of the backend of this server.
   *
   * @return string
   *   The plugin ID of the backend.
   */
  public function getBackendId();

  /**
   * Retrieves the backend.
   *
   * @return \Drupal\search_api\Backend\BackendInterface
   *   This server's backend plugin.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the backend plugin could not be retrieved.
   */
  public function getBackend();

  /**
   * Retrieves the configuration of this server's backend plugin.
   *
   * @return array
   *   An associative array with the backend configuration.
   */
  public function getBackendConfig();

  /**
   * Sets the configuration of this server's backend plugin.
   *
   * @param array $backend_config
   *   The new configuration for the backend.
   *
   * @return $this
   */
  public function setBackendConfig(array $backend_config);

  /**
   * Retrieves a list of indexes which use this server.
   *
   * @param array $properties
   *   (optional) Additional properties that the indexes should have.
   *
   * @return \Drupal\search_api\IndexInterface[]
   *   An array of all matching search indexes.
   */
  public function getIndexes(array $properties = array());

  /**
   * Deletes all items on this server, except those from read-only indexes.
   *
   * @return $this
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred while trying to delete the items.
   */
  public function deleteAllItems();

}

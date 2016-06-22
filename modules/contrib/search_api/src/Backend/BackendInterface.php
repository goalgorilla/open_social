<?php

namespace Drupal\search_api\Backend;

use Drupal\search_api\Plugin\ConfigurablePluginInterface;
use Drupal\search_api\ServerInterface;

/**
 * Defines an interface for search backend plugins.
 *
 * Consists of general plugin methods and the backend-specific methods defined
 * in \Drupal\search_api\Backend\BackendSpecificInterface, as well as special
 * CRUD "hook" methods that cannot be present on the server entity (which also
 * implements \Drupal\search_api\Backend\BackendSpecificInterface).
 *
 * @see \Drupal\search_api\Annotation\SearchApiBackend
 * @see \Drupal\search_api\Backend\BackendPluginManager
 * @see \Drupal\search_api\Backend\BackendPluginBase
 * @see plugin_api
 */
interface BackendInterface extends ConfigurablePluginInterface, BackendSpecificInterface {

  /**
   * Retrieves the server entity for this backend.
   *
   * @return \Drupal\search_api\ServerInterface
   *   The server entity.
   */
  public function getServer();

  /**
   * Sets the server entity for this backend.
   *
   * @param \Drupal\search_api\ServerInterface $server
   *   The server entity.
   *
   * @return $this
   */
  public function setServer(ServerInterface $server);

  /**
   * Reacts to the server's creation.
   *
   * Called once, when the server is first created. Allows the backend class to
   * set up its necessary infrastructure.
   */
  public function postInsert();

  /**
   * Notifies the backend that its configuration is about to be updated.
   *
   * The server's $original property can be used to inspect the old
   * configuration values.
   */
  public function preUpdate();

  /**
   * Notifies the backend that its configuration was updated.
   *
   * The server's $original property can be used to inspect the old
   * configuration values.
   *
   * @return bool
   *   TRUE, if the update requires reindexing of all content on the server.
   */
  public function postUpdate();

  /**
   * Notifies the backend that the server is about to be deleted.
   *
   * This should execute any necessary cleanup operations.
   *
   * Note that you shouldn't call the server's save() method, or any
   * methods that might do that, from inside of this method as the server isn't
   * present in the database anymore at this point.
   */
  public function preDelete();

}

<?php

namespace Drupal\search_api\Backend;

use Drupal\search_api\Entity\Server;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\ConfigurablePluginBase;
use Drupal\search_api\ServerInterface;

/**
 * Defines a base class for backend plugins.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_backend_info_alter(). The definition includes the following
 * keys:
 * - id: The unique, system-wide identifier of the backend class.
 * - label: The human-readable name of the backend class, translated.
 * - description: A human-readable description for the backend class,
 *   translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiBackend(
 *   id = "my_backend",
 *   label = @Translation("My backend"),
 *   description = @Translation("Searches with SuperSearch™.")
 * )
 * @endcode
 *
 * @see \Drupal\search_api\Annotation\SearchApiBackend
 * @see \Drupal\search_api\Backend\BackendPluginManager
 * @see \Drupal\search_api\Backend\BackendInterface
 * @see plugin_api
 */
abstract class BackendPluginBase extends ConfigurablePluginBase implements BackendInterface {

  /**
   * The server this backend is configured for.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The backend's server's ID.
   *
   * Used for serialization.
   *
   * @var string
   */
  protected $serverId;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (!empty($configuration['server']) && $configuration['server'] instanceof ServerInterface) {
      $this->setServer($configuration['server']);
      unset($configuration['server']);
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getServer() {
    return $this->server;
  }

  /**
   * {@inheritdoc}
   */
  public function setServer(ServerInterface $server) {
    $this->server = $server;
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFeature($feature) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDataType($type) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function postInsert() {}

  /**
   * {@inheritdoc}
   */
  public function preUpdate() {}

  /**
   * {@inheritdoc}
   */
  public function postUpdate() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete() {
    try {
      $this->getServer()->deleteAllItems();
    }
    catch (SearchApiException $e) {
      $vars = array(
        '%server' => $this->getServer()->label(),
      );
      watchdog_exception('search_api', $e, '%type while deleting items from server %server: @message in %function (line %line of %file).', $vars);
      drupal_set_message($this->t('Deleting some of the items on the server failed. Check the logs for details. The server was still removed.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex(IndexInterface $index) {}

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index) {}

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {}

  /**
   * {@inheritdoc}
   */
  public function getDiscouragedProcessors() {
    return array();
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Prevents the server entity from being serialized.
   */
  public function __sleep() {
    if ($this->server) {
      $this->serverId = $this->server->id();
    }
    $properties = array_flip(parent::__sleep());
    unset($properties['server']);
    return array_keys($properties);
  }

  /**
   * Implements the magic __wakeup() method.
   *
   * Reloads the server entity.
   */
  public function __wakeup() {
    parent::__wakeup();

    if ($this->serverId) {
      $this->server = Server::load($this->serverId);
      $this->serverId = NULL;
    }
  }

  /**
   * Retrieves the effective fulltext fields from the query.
   *
   * Automatically translates a NULL value in the query object to all fulltext
   * fields in the search index.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query.
   *
   * @return string[]
   *   The fulltext fields in which to search for the search keys.
   *
   * @see \Drupal\search_api\Query\QueryInterface::getFulltextFields()
   */
  protected function getQueryFulltextFields(QueryInterface $query) {
    $fulltext_fields = $query->getFulltextFields();
    return $fulltext_fields === NULL ? $query->getIndex()->getFulltextFields() : $fulltext_fields;
  }

}

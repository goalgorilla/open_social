<?php

namespace Drupal\search_api\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\ServerInterface;

/**
 * Defines the search server configuration entity.
 *
 * @ConfigEntityType(
 *   id = "search_api_server",
 *   label = @Translation("Search server"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "form" = {
 *       "default" = "Drupal\search_api\Form\ServerForm",
 *       "edit" = "Drupal\search_api\Form\ServerForm",
 *       "delete" = "Drupal\search_api\Form\ServerDeleteConfirmForm",
 *       "disable" = "Drupal\search_api\Form\ServerDisableConfirmForm",
 *       "clear" = "Drupal\search_api\Form\ServerClearConfirmForm"
 *     },
 *   },
 *   admin_permission = "administer search_api",
 *   config_prefix = "server",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "description",
 *     "backend",
 *     "backend_config",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/search-api/index/{search_api_server}",
 *     "add-form" = "/admin/config/search/search-api/add-server",
 *     "edit-form" = "/admin/config/search/search-api/index/{search_api_server}/edit",
 *     "delete-form" = "/admin/config/search/search-api/index/{search_api_server}/delete",
 *     "disable" = "/admin/config/search/search-api/index/{search_api_server}/disable",
 *     "enable" = "/admin/config/search/search-api/index/{search_api_server}/enable",
 *   }
 * )
 */
class Server extends ConfigEntityBase implements ServerInterface {

  /**
   * The ID of the server.
   *
   * @var string
   */
  protected $id;

  /**
   * The displayed name of the server.
   *
   * @var string
   */
  protected $name;

  /**
   * The displayed description of the server.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The ID of the backend plugin.
   *
   * @var string
   */
  protected $backend;

  /**
   * The backend plugin configuration.
   *
   * @var array
   */
  protected $backend_config = array();

  /**
   * The backend plugin instance.
   *
   * @var \Drupal\search_api\Backend\BackendInterface
   */
  protected $backendPlugin;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidBackend() {
    $backend_plugin_definition = \Drupal::service('plugin.manager.search_api.backend')->getDefinition($this->getBackendId(), FALSE);
    return !empty($backend_plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getBackendId() {
    return $this->backend;
  }

  /**
   * {@inheritdoc}
   */
  public function getBackend() {
    if (!$this->backendPlugin) {
      $backend_plugin_manager = \Drupal::service('plugin.manager.search_api.backend');
      $config = $this->backend_config;
      $config['server'] = $this;
      if (!($this->backendPlugin = $backend_plugin_manager->createInstance($this->getBackendId(), $config))) {
        $args['@backend'] = $this->getBackendId();
        $args['%server'] = $this->label();
        throw new SearchApiException(new FormattableMarkup('The backend with ID "@backend" could not be retrieved for server %server.', $args));
      }
    }
    return $this->backendPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getBackendConfig() {
    return $this->backend_config;
  }

  /**
   * {@inheritdoc}
   */
  public function setBackendConfig(array $backend_config) {
    $this->backend_config = $backend_config;
    $this->getBackend()->setConfiguration($backend_config);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexes(array $properties = array()) {
    $storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    return $storage->loadByProperties(array('server' => $this->id()) + $properties);
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    return $this->hasValidBackend() ? $this->getBackend()->viewSettings() : array();
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return $this->hasValidBackend() ? $this->getBackend()->isAvailable() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFeature($feature) {
    return $this->hasValidBackend() ? $this->getBackend()->supportsFeature($feature) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDataType($type) {
    return $this->hasValidBackend() ? $this->getBackend()->supportsDataType($type) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDiscouragedProcessors() {
    return $this->getBackend()->getDiscouragedProcessors();
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex(IndexInterface $index) {
    $server_task_manager = \Drupal::getContainer()->get('search_api.server_task_manager');
    // When freshly adding an index to a server, it doesn't make any sense to
    // execute possible other tasks for that server/index combination.
    // (removeIndex() is implicit when adding an index which was already added.)
    $server_task_manager->delete(NULL, $this, $index);

    try {
      $this->getBackend()->addIndex($index);
    }
    catch (SearchApiException $e) {
      $vars = array(
        '%server' => $this->label(),
        '%index' => $index->label(),
      );
      watchdog_exception('search_api', $e, '%type while adding index %index to server %server: @message in %function (line %line of %file).', $vars);
      $server_task_manager->add($this, __FUNCTION__, $index);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index) {
    $server_task_manager = \Drupal::getContainer()->get('search_api.server_task_manager');
    try {
      if ($server_task_manager->execute($this)) {
        $this->getBackend()->updateIndex($index);
        return;
      }
    }
    catch (SearchApiException $e) {
      $vars = array(
        '%server' => $this->label(),
        '%index' => $index->label(),
      );
      watchdog_exception('search_api', $e, '%type while updating the fields of index %index on server %server: @message in %function (line %line of %file).', $vars);
    }
    $server_task_manager->add($this, __FUNCTION__, $index, isset($index->original) ? $index->original : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {
    $server_task_manager = \Drupal::getContainer()->get('search_api.server_task_manager');
    // When removing an index from a server, it doesn't make any sense anymore
    // to delete items from it, or react to other changes.
    $server_task_manager->delete(NULL, $this, $index);

    try {
      $this->getBackend()->removeIndex($index);
    }
    catch (SearchApiException $e) {
      $vars = array(
        '%server' => $this->label(),
        '%index' => is_object($index) ? $index->label() : $index,
      );
      watchdog_exception('search_api', $e, '%type while removing index %index from server %server: @message in %function (line %line of %file).', $vars);
      $server_task_manager->add($this, __FUNCTION__, $index);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    $server_task_manager = \Drupal::getContainer()->get('search_api.server_task_manager');
    if ($server_task_manager->execute($this)) {
      return $this->getBackend()->indexItems($index, $items);
    }
    $args['%index'] = $index->label();
    throw new SearchApiException(new FormattableMarkup('Could not index items on index %index because pending server tasks could not be executed.', $args));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids) {
    if ($index->isReadOnly()) {
      $vars = array(
        '%index' => $index->label(),
      );
      \Drupal::logger('search_api')->warning('Trying to delete items from index %index which is marked as read-only.', $vars);
      return;
    }

    $server_task_manager = \Drupal::getContainer()->get('search_api.server_task_manager');
    try {
      if ($server_task_manager->execute($this)) {
        $this->getBackend()->deleteItems($index, $item_ids);
        return;
      }
    }
    catch (SearchApiException $e) {
      $vars = array(
        '%server' => $this->label(),
      );
      watchdog_exception('search_api', $e, '%type while deleting items from server %server: @message in %function (line %line of %file).', $vars);
    }
    $server_task_manager->add($this, __FUNCTION__, $index, $item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index) {
    if ($index->isReadOnly()) {
      $vars = array(
        '%index' => $index->label(),
      );
      \Drupal::logger('search_api')->warning('Trying to delete items from index %index which is marked as read-only.', $vars);
      return;
    }

    $server_task_manager = \Drupal::getContainer()->get('search_api.server_task_manager');
    try {
      if ($server_task_manager->execute($this)) {
        $this->getBackend()->deleteAllIndexItems($index);
        return;
      }
    }
    catch (SearchApiException $e) {
      $vars = array(
        '%server' => $this->label(),
        '%index' => $index->label(),
      );
      watchdog_exception('search_api', $e, '%type while deleting items of index %index from server %server: @message in %function (line %line of %file).', $vars);
    }
    $server_task_manager->add($this, __FUNCTION__, $index);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllItems() {
    $failed = array();
    $properties['status'] = TRUE;
    $properties['read_only'] = FALSE;
    foreach ($this->getIndexes($properties) as $index) {
      try {
        $this->getBackend()->deleteAllIndexItems($index);
      }
      catch (SearchApiException $e) {
        $args = array(
          '%index' => $index->label(),
        );
        watchdog_exception('search_api', $e, '%type while deleting all items from index %index: @message in %function (line %line of %file).', $args);
        $failed[] = $index->label();
      }
    }
    if (!empty($e)) {
      $args = array(
        '%server' => $this->label(),
        '@indexes' => implode(', ', $failed),
      );
      $message = new FormattableMarkup('Deleting all items from server %server failed for the following (write-enabled) indexes: @indexes.', $args);
      throw new SearchApiException($message, 0, $e);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
    return $this->getBackend()->search($query);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // The rest of the code only applies to updates.
    if (!isset($this->original)) {
      return;
    }

    $this->getBackend()->preUpdate();

    // If the server is being disabled, also disable all its indexes.
    if (!$this->status() && $this->original->status()) {
      foreach ($this->getIndexes(array('status' => TRUE)) as $index) {
        /** @var \Drupal\search_api\IndexInterface $index */
        $index->setStatus(FALSE)->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    if ($this->hasValidBackend()) {
      if ($update) {
        $reindexing_necessary = $this->getBackend()->postUpdate();
        if ($reindexing_necessary) {
          foreach ($this->getIndexes() as $index) {
            $index->reindex();
          }
        }
      }
      else {
        $this->getBackend()->postInsert();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    // @todo This will, via Index::onDependencyRemoval(), remove all indexes
    //   from this server, triggering the server's removeIndex() method. This
    //   is, at best, wasted performance and could at worst lead to a bug if
    //   removeIndex() saves the server. We should try what happens when this is
    //   the case, whether there really is a bug, and try to fix it somehow –
    //   maybe clever detection of this case in removeIndex() or
    //   Index::postSave(). $server->isUninstalling() might help?
    parent::preDelete($storage, $entities);

    // Iterate through the servers, executing the backends' preDelete() methods
    // and removing all their pending server tasks.
    foreach ($entities as $server) {
      /** @var \Drupal\search_api\ServerInterface $server */
      if ($server->hasValidBackend()) {
        $server->getBackend()->preDelete();
      }
      \Drupal::getContainer()->get('search_api.server_task_manager')->delete(NULL, $server);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    // @todo It's a bug that we have to do this. Backend configuration should
    //   always be set via the server's setBackendConfiguration() method,
    //   otherwise the two can diverge causing this and other problems. The
    //   alternative would be to call $server->setBackendConfiguration() in the
    //   backend's setConfiguration() method and use a second $propagate
    //   parameter to avoid an infinite loop. Similar things go for the index's
    //   various plugins. Maybe using plugin bags is the solution here?
    $properties = parent::toArray();
    if ($this->hasValidBackend()) {
      $properties['backend_config'] = $this->getBackend()->getConfiguration();
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add the backend's dependencies.
    if ($this->hasValidBackend()) {
      $this->calculatePluginDependencies($this->getBackend());
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    if ($this->hasValidBackend()) {
      $removed_backend_dependencies = array();
      $backend = $this->getBackend();
      foreach ($backend->calculateDependencies() as $dependency_type => $list) {
        if (isset($dependencies[$dependency_type])) {
          $removed_backend_dependencies[$dependency_type] = array_intersect_key($dependencies[$dependency_type], array_flip($list));
        }
      }
      $removed_backend_dependencies = array_filter($removed_backend_dependencies);
      if ($removed_backend_dependencies) {
        if ($backend->onDependencyRemoval($removed_backend_dependencies)) {
          $this->backend_config = $backend->getConfiguration();
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

  /**
   * Implements the magic __clone() method.
   *
   * Prevents the backend plugin instance from being cloned.
   */
  public function __clone() {
    $this->backendPlugin = NULL;
  }

}

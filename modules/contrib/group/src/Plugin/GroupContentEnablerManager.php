<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerManager.
 */

namespace Drupal\group\Plugin;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Manages GroupContentEnabler plugin implementations.
 *
 * @see hook_group_content_info_alter()
 * @see \Drupal\group\Annotation\GroupContentEnabler
 * @see \Drupal\group\Plugin\GroupContentEnablerInterface
 * @see \Drupal\group\Plugin\GroupContentEnablerBase
 * @see plugin_api
 */
class GroupContentEnablerManager extends DefaultPluginManager implements GroupContentEnablerManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A collection of vanilla instances of all content enabler plugins.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerCollection
   */
  protected $allPlugins;

  /**
   * A list of all installed content enabler plugin IDs.
   *
   * @var string[]
   */
  protected $installedIds;

  /**
   * The cache key for the installed content enabler plugin IDs.
   *
   * @var string
   */
  protected $installedIdsCacheKey;

  /**
   * An static cache of group content type IDs per plugin ID.
   *
   * @var array[]
   */
  protected $groupContentTypeIdMap;

  /**
   * The cache key for the group content type ID map.
   *
   * @var string
   */
  protected $groupContentTypeIdMapCacheKey;

  /**
   * Constructs a GroupContentEnablerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/GroupContentEnabler', $namespaces, $module_handler, 'Drupal\group\Plugin\GroupContentEnablerInterface', 'Drupal\group\Annotation\GroupContentEnabler');
    $this->alterInfo('group_content_info');
    $this->setCacheBackend($cache_backend, 'group_content_enablers');
    $this->entityTypeManager = $entity_type_manager;
    $this->installedIdsCacheKey = $this->cacheKey . '_installed';
    $this->groupContentTypeIdMapCacheKey = $this->cacheKey . '_GCT_map';
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    if (!isset($this->allPlugins)) {
      $collection = new GroupContentEnablerCollection($this, []);

      // Add every known plugin to the collection with a vanilla configuration.
      foreach ($this->getDefinitions() as $plugin_id => $plugin_info) {
        $collection->setInstanceConfiguration($plugin_id, ['id' => $plugin_id]);
      }

      // Sort and set the plugin collection.
      $this->allPlugins = $collection->sort();
    }

    return $this->allPlugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalled() {
    // Retrieve a vanilla instance of all known content enabler plugins.
    $plugins = clone $this->getAll();
    
    // Retrieve all installed content enabler plugin IDs.
    $installed = $this->getInstalledIds();

    // Remove uninstalled plugins from the collection.
    /** @var \Drupal\group\Plugin\GroupContentEnablerCollection $plugins */
    foreach ($plugins as $plugin_id => $plugin) {
      if (!in_array($plugin_id, $installed)) {
        $plugins->removeInstanceId($plugin_id);
      }
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalledIds() {
    $plugin_ids = $this->getCachedInstalledIDs();

    if (!isset($plugin_ids)) {
      $plugin_ids = [];

      // Installed plugins can only live on a group type. By retrieving all of
      // the group types' plugin configuration array, we can build a list of
      // installed plugin IDs.
      /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
      $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();
      foreach ($group_types as $group_type) {
        $plugin_ids = array_merge($plugin_ids, array_keys($group_type->get('content')));
      }

      $plugin_ids = array_unique($plugin_ids);
      $this->setCachedInstalledIDs($plugin_ids);
    }

    return $plugin_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedInstalledIDs() {
    if ($this->cacheBackend) {
      $this->cacheBackend->delete($this->installedIdsCacheKey);
    }
    $this->installedIds = NULL;
  }

  /**
   * Returns the cached installed plugin IDs.
   *
   * @return array|null
   *   On success this will return the installed plugin ID list. On failure this
   *   should return NULL, indicating to other methods that this has not yet
   *   been defined. Success with no values should return as an empty array.
   */
  protected function getCachedInstalledIDs() {
    if (!isset($this->installedIds) && $cache = $this->cacheGet($this->installedIdsCacheKey)) {
      $this->installedIds = $cache->data;
    }
    return $this->installedIds;
  }

  /**
   * Sets a cache of the installed plugin IDs.
   *
   * @param array $plugin_ids
   *   The installed plugin IDs to store in cache.
   */
  protected function setCachedInstalledIDs($plugin_ids) {
    $this->cacheSet($this->installedIdsCacheKey, $plugin_ids, Cache::PERMANENT);
    $this->installedIds = $plugin_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function installEnforced(GroupTypeInterface $group_type = NULL) {
    $enforced = [];

    // Gather the ID of all plugins that are marked as enforced.
    foreach ($this->getDefinitions() as $plugin_id => $plugin_info) {
      if ($plugin_info['enforced']) {
        $enforced[] = $plugin_id;
      }
    }

    // If no group type was specified, we check all of them.
    /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
    $group_types = empty($group_type)
      ? $this->entityTypeManager->getStorage('group_type')->loadMultiple()
      : [$group_type];

    // Search through all of the enforced plugins and install new ones.
    foreach ($group_types as $group_type) {
      foreach ($enforced as $plugin_id) {
        if (!$group_type->hasContentPlugin($plugin_id)) {
          $group_type->installContentPlugin($plugin_id);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupContentTypeIds($plugin_id) {
    $map = $this->getCachedGroupContentTypeIdMap();

    if (!isset($map)) {
      $map = [];

      /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $group_content_types */
      $group_content_types = $this->entityTypeManager->getStorage('group_content_type')->loadMultiple();
      foreach ($group_content_types as $group_content_type) {
        $map[$group_content_type->getContentPluginId()][] = $group_content_type->id();
      }

      $this->setCachedGroupContentTypeIdMap($map);
    }

    return isset($map[$plugin_id]) ? $map[$plugin_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedGroupContentTypeIdMap() {
    if ($this->cacheBackend) {
      $this->cacheBackend->delete($this->groupContentTypeIdMapCacheKey);
    }
    $this->groupContentTypeIdMap = NULL;
  }

  /**
   * Returns the cached group content type ID map.
   *
   * @return array|null
   *   On success this will return the group content ID map (array). On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array.
   */
  protected function getCachedGroupContentTypeIdMap() {
    if (!isset($this->groupContentTypeIdMap) && $cache = $this->cacheGet($this->groupContentTypeIdMapCacheKey)) {
      $this->groupContentTypeIdMap = $cache->data;
    }
    return $this->groupContentTypeIdMap;
  }

  /**
   * Sets a cache of the group content type ID map.
   *
   * @param array $map
   *   The group content type ID map to store in cache.
   */
  protected function setCachedGroupContentTypeIdMap($map) {
    $this->cacheSet($this->groupContentTypeIdMapCacheKey, $map, Cache::PERMANENT);
    $this->groupContentTypeIdMap = $map;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();

    // The collection of all plugins should only change if the plugin
    // definitions change, so we can safely reset that here.
    $this->allPlugins = NULL;
  }
  
}

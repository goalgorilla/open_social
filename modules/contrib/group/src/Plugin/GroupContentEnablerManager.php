<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerManager.
 */

namespace Drupal\group\Plugin;

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
  protected $installedPluginIds;

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
  public function getInstalledIds() {
    if (!isset($this->installedPluginIds)) {
      $plugin_ids = [];

      // Installed plugins can only live on a group type. By retrieving all of
      // the group types' plugin configuration array, we can build a list of
      // installed plugin IDs.
      /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
      $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();
      foreach ($group_types as $group_type) {
        $plugin_ids = array_merge($plugin_ids, array_keys($group_type->get('content')));
      }

      $this->installedPluginIds = array_unique($plugin_ids);
    }

    return $this->installedPluginIds;
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
  public function reset() {
    $this->allPlugins = NULL;
    $this->installedPluginIds = [];
  }
  
}

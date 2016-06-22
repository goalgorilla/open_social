<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerManager.
 */

namespace Drupal\group\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages GroupContentEnabler plugin implementations.
 *
 * @see hook_group_content_info_alter()
 * @see \Drupal\group\Annotation\GroupContentEnabler
 * @see \Drupal\group\Plugin\GroupContentEnablerInterface
 * @see \Drupal\group\Plugin\GroupContentEnablerBase
 * @see plugin_api
 */
class GroupContentEnablerManager extends DefaultPluginManager {

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
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GroupContentEnabler', $namespaces, $module_handler, 'Drupal\group\Plugin\GroupContentEnablerInterface', 'Drupal\group\Annotation\GroupContentEnabler');
    $this->alterInfo('group_content_info');
    $this->setCacheBackend($cache_backend, 'group_content_enablers');
  }

}

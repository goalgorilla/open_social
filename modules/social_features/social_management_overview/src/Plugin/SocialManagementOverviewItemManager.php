<?php

namespace Drupal\social_management_overview\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Social management overview item plugin manager.
 */
class SocialManagementOverviewItemManager extends DefaultPluginManager {

  /**
   * Constructs a new SocialManagementOverviewItemManager object.
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
    parent::__construct('Plugin/SocialManagementOverviewItem', $namespaces, $module_handler, 'Drupal\social_management_overview\Plugin\SocialManagementOverviewItemInterface', 'Drupal\social_management_overview\Annotation\SocialManagementOverviewItem');

    $this->alterInfo('social_management_overview_item_info');
    $this->setCacheBackend($cache_backend, 'social_management_overview_item_plugins');
  }

  /**
   * Returns children of provided overview group.
   *
   * @param string $group
   *   Overview group plugin ID.
   *
   * @return array
   *   List of children.
   */
  public function getChildren(string $group): array {
    return array_filter($this->getDefinitions(), static function ($definition) use ($group) {
      if (!isset($definition['group'])) {
        return FALSE;
      }

      return $definition['group'] === $group;
    });
  }

}

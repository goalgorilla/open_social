<?php

/**
 * @file
 * Contains \Drupal\address\ZoneMemberManager.
 */

namespace Drupal\address;

use Drupal\address\Entity\ZoneInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages zone member plugins.
 */
class ZoneMemberManager extends DefaultPluginManager {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs a new ZoneMemberManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The uuid service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, UuidInterface $uuid_service) {
    parent::__construct('Plugin/ZoneMember', $namespaces, $module_handler, 'Drupal\address\Plugin\ZoneMember\ZoneMemberInterface', 'Drupal\address\Annotation\ZoneMember');

    $this->alterInfo('zone_member_info');
    $this->setCacheBackend($cache_backend, 'zone_member_plugins');
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   *
   * Passes the $parent_zone along to the instantiated plugin.
   */
  public function createInstance($plugin_id, array $configuration = [], ZoneInterface $parent_zone = NULL) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_definition['parent_zone'] = $parent_zone;
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    // Generate an id for the plugin instance, if it wasn't provided.
    if (empty($configuration['id'])) {
      $configuration['id'] = $this->uuidService->generate();
    }
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      $plugin = $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition, $parent_zone);
    }
    else {
      $plugin = new $plugin_class($configuration, $plugin_id, $plugin_definition, $parent_zone);
    }

    return $plugin;
  }

}

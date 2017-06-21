<?php

namespace Drupal\social_demo;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class DemoContentManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/DemoContent',
      $namespaces,
      $module_handler,
      'Drupal\social_demo\DemoContentInterface',
      'Drupal\social_demo\Annotation\DemoContent'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    /** @var \Drupal\social_demo\DemoContentInterface $plugin */
    $plugin = parent::createInstance($plugin_id, $configuration);
    $definition = $plugin->getPluginDefinition();
    $storage = \Drupal::entityTypeManager()->getStorage($definition['entity_type']);
    $plugin->setEntityStorage($storage);

    return $plugin;
  }

  /**
   * Create multiple instances.
   *
   * @param array $plugin_ids
   *   Identifiers of plugins.
   * @param array $configurations
   *   Array with configuration for all plugins.
   * @return array
   *   Array with instances of the plugins.
   */
  public function createInstances($plugin_ids, array $configurations = []) {
    $instances = [];

    foreach ($plugin_ids as $plugin_id) {
      $configuration = isset($configurations[$plugin_id]) ? $configurations[$plugin_id] : [];
      $instances[$plugin_id] = static::createInstance($plugin_id, $configuration);
    }

    return $instances;
  }

}

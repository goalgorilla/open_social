<?php

namespace Drupal\mentions;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * MentionsPluginManager for Mentions Type.
 */
class MentionsPluginManager extends DefaultPluginManager {
  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Mentions', $namespaces, $module_handler, 'Drupal\mentions\MentionsPluginInterface', 'Drupal\mentions\Annotation\Mention');
    $this->alterInfo('mentions_plugin_info');
    $this->setCacheBackend($cache_backend, 'mentions_plugins');
  }

  /**
   * Get the names of plugins of type mentions_type.
   *
   * @return array
   */
  public function getPluginNames() {
    $definitions = $this->getDefinitions();
    $plugin_names = array();

    foreach ($definitions as $definition) {
      //array_push($plugin_names, $definition['name']->getUntranslatedString());
      $name = $definition['name']->getUntranslatedString();
      $plugin_names[$definition['id']] = $name;
    }

    return $plugin_names;
  }
  


}

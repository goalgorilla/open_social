<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityEntityConditionManager.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides the Activity entity condition plugin manager.
 */
class ActivityEntityConditionManager extends DefaultPluginManager {

  /**
   * Constructor for ActivityEntityConditionManager objects.
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
    parent::__construct('Plugin/ActivityEntityCondition', $namespaces, $module_handler, 'Drupal\activity_creator\Plugin\ActivityEntityConditionInterface', 'Drupal\activity_creator\Annotation\ActivityEntityCondition');

    $this->alterInfo('activity_creator_activity_entity_condition_info');
    $this->setCacheBackend($cache_backend, 'activity_creator_activity_entity_condition_plugins');
  }

  /**
   * Retrieves an options list of available trackers.
   *
   * @param string $entity
   *   Value of activity_bundle_entity in format "entity.bundle"
   *
   * @return string[]
   *   An associative array mapping the IDs of all available tracker plugins to
   *   their labels.
   */
  public function getOptionsList($entity = NULL) {
    $options = array();

    $entity = explode('.', $entity);
    $entity_type = $entity[0];
    $bundle = $entity[1];
    // Get all entity condition plugin definitions.
    foreach ($this->getDefinitions() as $plugin_id => $plugin_definition) {
      if (!empty($entity)) {
        if (isset($plugin_definition['entities'][$entity_type])) {
          // If only entity type is set in plugin.
          if (empty($plugin_definition['entities'][$entity_type])) {
            $options[$plugin_id] = Html::escape($plugin_definition['label']);
          }
          // If entity type and bundle(s) are set in plugin.
          else {
            if (in_array($bundle, $plugin_definition['entities'][$entity_type])) {
              $options[$plugin_id] = Html::escape($plugin_definition['label']);
            }
          }
        }
      }
      else {
        $options[$plugin_id] = Html::escape($plugin_definition['label']);
      }
    }
    return $options;
  }

}

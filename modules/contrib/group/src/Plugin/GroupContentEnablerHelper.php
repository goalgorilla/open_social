<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerHelper.
 */

namespace Drupal\group\Plugin;

use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Facilitates the installation of GroupContentEnabler plugins.
 */
class GroupContentEnablerHelper {

  /**
   * A collection of instances of all content enabler plugins.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerCollection
   */
  protected static $pluginCollection;

  /**
   * The ID of every installed content enabler plugin.
   *
   * @var string[]
   */
  protected static $installedPluginIds;

  /**
   * Prevents this class from being instantiated.
   */
  private function __construct() {}

  /**
   * Returns a plugin collection of all available content enablers.
   *
   * We add all known plugins to one big collection so we can sort them using
   * the sorting logic available on the collection and so we're sure we're not
   * instantiating our vanilla plugins more than once.
   *
   * This collection will not have any group type set in the individual plugins'
   * configuration. Do not use any methods on the plugin that require a group
   * type to be set or you may encounter unexpected behavior. Instead, use
   * GroupTypeInterface::getInstalledContentPlugins()->get($plugin_id).
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   The content enabler plugin collection.
   */
  public static function getAllContentEnablers() {
    if (!isset(self::$pluginCollection)) {
      $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');
      $collection = new GroupContentEnablerCollection($plugin_manager, []);

      // Add every known plugin to the collection with a vanilla configuration.
      foreach ($plugin_manager->getDefinitions() as $plugin_id => $plugin_info) {
        $collection->setInstanceConfiguration($plugin_id, ['id' => $plugin_id]);
      }

      // Sort and set the plugin collection.
      self::$pluginCollection = $collection->sort();
    }

    return self::$pluginCollection;
  }

  /**
   * Returns the plugin ID of all content enablers in use.
   *
   * @return string[]
   *   A list of content enabler plugin IDs.
   */
  public static function getInstalledContentEnablerIDs() {
    if (!isset(self::$installedPluginIds)) {
      $plugin_ids = [];

      // Group content types can only exist if a plugin was installed for them.
      // By asking all of the existing content types for their plugin ID, we are
      // sure to have built a list of only installed plugins.
      foreach (GroupContentType::loadMultiple() as $group_content_type) {
        $plugin_ids[] = $group_content_type->getContentPluginId();
      }

      self::$installedPluginIds = array_unique($plugin_ids);
    }

    return self::$installedPluginIds;
  }

  /**
   * Returns a list of additional forms to enable for group content entities.
   *
   * @return array
   *   An associative array with form names as keys and class names as values.
   */
  public static function getAdditionalEntityForms() {
    $forms = [];

    // Retrieve all installed content enabler plugins.
    $installed = self::getInstalledContentEnablerIDs();

    // Retrieve all possible forms from all installed plugins.
    foreach (self::getAllContentEnablers() as $plugin_id => $plugin) {
      // Skip plugins that have not been installed anywhere.
      if (!in_array($plugin_id, $installed)) {
        continue;
      }

      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $forms = array_merge($forms, $plugin->getEntityForms());
    }

    return $forms;
  }

  /**
   * Installs all plugins which are marked as enforced.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to install enforced plugins for. Leave blank to
   *   run the installation process for all group types.
   */
  public static function installEnforcedPlugins(GroupTypeInterface $group_type = NULL) {
    $enforced = [];
    foreach (self::getAllContentEnablers() as $plugin_id => $plugin) {
      /** @var GroupContentEnablerInterface $plugin */
      if ($plugin->isEnforced()) {
        $enforced[] = $plugin_id;
      }
    }

    $group_types = empty($group_type) ? GroupType::loadMultiple() : [$group_type];
    foreach ($group_types as $group_type) {
      // Search through all the enforced plugins and install new ones.
      foreach ($enforced as $plugin_id) {
        if (!$group_type->hasContentPlugin($plugin_id)) {
          $group_type->installContentPlugin($plugin_id);
        }
      }
    }
  }

  /**
   * Resets the static properties on this class.
   */
  public static function reset() {
    self::$pluginCollection = NULL;
    self::$installedPluginIds = [];
  }

}

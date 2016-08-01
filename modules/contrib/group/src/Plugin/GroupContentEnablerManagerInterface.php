<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerManagerInterface.
 */

namespace Drupal\group\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides a common interface for group content enabler managers.
 */
interface GroupContentEnablerManagerInterface extends PluginManagerInterface {

  /**
   * Returns a plugin collection of all available content enablers.
   *
   * This collection will not have anything set in the individual plugins'
   * configuration. Do not use any methods on the plugin that require a group
   * type to be set or you may encounter unexpected behavior. Instead, use
   * GroupTypeInterface::getInstalledContentPlugins()->get($plugin_id) to get
   * fully configured instances of the plugins.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   A plugin collection with a vanilla instance of every known plugin.
   */
  public function getAll();

  /**
   * Returns a plugin collection of all installed content enablers.
   *
   * This collection will not have anything set in the individual plugins'
   * configuration. Do not use any methods on the plugin that require a group
   * type to be set or you may encounter unexpected behavior. Instead, use
   * GroupTypeInterface::getInstalledContentPlugins()->get($plugin_id) to get
   * fully configured instances of the plugins.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   A plugin collection with a vanilla instance of every installed plugin.
   */
  public function getInstalled();
  
  /**
   * Returns the plugin ID of all content enablers in use.
   *
   * Seeing as a plugin can be installed on multiple group types, we cannot
   * safely instantiate plugin for the retrieved IDs because we would not know
   * which group type to create an instance for.
   *
   * @return string[]
   *   A list of all installed content enabler plugin IDs.
   */
  public function getInstalledIds();

  /**
   * Clears static and persistent installed plugin ID caches.
   */
  public function clearCachedInstalledIds();

  /**
   * Installs all plugins which are marked as enforced.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to install enforced plugins for. Leave blank to
   *   run the installation process for all group types.
   */
  public function installEnforced(GroupTypeInterface $group_type = NULL);

  /**
   * Retrieves all of the group content types for a plugin.
   *
   * @param $plugin_id
   *   The ID of the plugin to retrieve GroupContentType entity IDs for.
   * 
   * @return string[]
   *   An array of GroupContentType IDs.
   */
  public function getGroupContentTypeIds($plugin_id);

  /**
   * Clears static and persistent group content type ID map caches.
   */
  public function clearCachedGroupContentTypeIdMap();

}

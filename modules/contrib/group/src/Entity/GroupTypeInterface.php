<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupTypeInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface defining a group type entity.
 */
interface GroupTypeInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * The maximum length of the ID, in characters.
   *
   * This is shorter than the default limit of 32 to allow group roles to have
   * an ID which can be appended to the group type's ID without exceeding the
   * default limit there. We leave of 10 characters to account for '-anonymous'.
   */
  const ID_MAX_LENGTH = 22;

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this group type.
   */
  public function getDescription();

  /**
   * Gets the group roles.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The group roles this group type uses.
   */
  public function getRoles();

  /**
   * Gets the role IDs.
   *
   * @return string[]
   *   The ids of the group roles this group type uses.
   */
  public function getRoleIds();

  /**
   * Returns the installed content enabler plugins for this group type.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   The group content plugin collection.
   */
  public function getInstalledContentPlugins();

  /**
   * Checks whether a content enabler plugin is installed for this group type.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin to check for.
   *
   * @return bool
   *   Whether the content enabler plugin is installed.
   */
  public function hasContentPlugin($plugin_id);

  /**
   * Gets an installed content enabler plugin for this group type.
   *
   * Warning: In places where the plugin may not be installed on the group type,
   * you should always run ::hasContentPlugin() first or you may risk ending up
   * with crashes or unreliable data.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The installed content enabler plugin for the group type.
   */
  public function getContentPlugin($plugin_id);

  /**
   * Adds a content enabler plugin to this group type.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin to add.
   * @param array $configuration
   *   (optional) An array of content enabler plugin configuration.
   *
   * @return $this
   */
  public function installContentPlugin($plugin_id, array $configuration = []);

  /**
   * Updates the configuration of a content enabler plugin for this group type.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin to add.
   * @param array $configuration
   *   An array of content enabler plugin configuration.
   *
   * @return $this
   */
  public function updateContentPlugin($plugin_id, array $configuration);

  /**
   * Removes a content enabler plugin from this group type.
   *
   * @param string $plugin_id
   *   The content enabler plugin ID.
   *
   * @return $this
   */
  public function uninstallContentPlugin($plugin_id);

}

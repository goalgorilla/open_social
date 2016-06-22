<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesExtensionStoragesInterface.
 */

namespace Drupal\features;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Extension\Extension;

/**
 * The FeaturesExtensionStorages provides a collection of extension storages,
 * one for each supported configuration directory.
 *
 * Typically this will include the install and optional directories defined by
 * Drupal core, but may also include any extension configuration directories
 * added by contributed modules.
 *
 * This class serves as a partial wrapper to
 * Drupal\Core\Config\StorageInterface, providing a subset of methods that can
 * be called to apply to all available extension storages. For example,
 * FeaturesExtensionStoragesInterface::read() will read an extension-provided
 * configuration item regardless of which extension storage directory it is
 * provided in.
 */
interface FeaturesExtensionStoragesInterface {

  /**
   * Returns all registered extension storages.
   *
   * @return FeaturesInstallStorage[]
   *   Array of install storages keyed by configuration directory.
   */
  public function getExtensionStorages();

  /**
   * Adds a storage.
   *
   * @param string $directory
   *   (optional) The configuration directory. If omitted,
   *   InstallStorage::CONFIG_INSTALL_DIRECTORY will be used.
   */
  public function addStorage($directory = InstallStorage::CONFIG_INSTALL_DIRECTORY);

  /**
   * Reads configuration data from the storages.
   *
   * @param string $name
   *   The name of a configuration object to load.
   *
   * @return array|bool
   *   The configuration data stored for the configuration object name. If no
   *   configuration data exists for the given name, FALSE is returned.
   */
  public function read($name);

  /**
   * Gets configuration object names starting with a given prefix.
   *
   * Given the following configuration objects:
   * - node.type.article
   * - node.type.page
   *
   * Passing the prefix 'node.type.' will return an array containing the above
   * names.
   *
   * @param string $prefix
   *   (optional) The prefix to search for. If omitted, all configuration object
   *   names that exist are returned.
   *
   * @return array
   *   An array containing matching configuration object names.
   */
  public function listAll($prefix = '');

  /**
   * Lists names of configuration objects provided by a given extension.
   *
   * If a $name and/or $namespace is specified, only matching modules will be
   * returned. Otherwise, all install are returned.
   *
   * @param mixed $extension
   *   A string name of an extension or a full Extension object.
   *
   * @return array
   *   An array of configuration object names.
   */
  public function listExtensionConfig(Extension $extension);

}

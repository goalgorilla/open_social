<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesManagerInterface.
 */

namespace Drupal\features;

use Drupal\Component\Serialization\Yaml;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesBundleInterface;
use Drupal\features\FeaturesGeneratorInterface;
use Drupal\Core\Extension\Extension;

/**
 * Provides an interface for the FeaturesManager.
 */
interface FeaturesManagerInterface {

  /**
   * Simple configuration.
   *
   * Core uses system.simple, but since we're using this key in configuration
   * arrays we can't include a period.
   *
   * @see https://www.drupal.org/node/2297311
   */
  const SYSTEM_SIMPLE_CONFIG = 'system_simple';

  /**
   * Constants for package/module status.
   */
  const STATUS_NO_EXPORT = 0;
  const STATUS_UNINSTALLED = 1;
  const STATUS_INSTALLED = 2;
  const STATUS_DEFAULT = self::STATUS_NO_EXPORT;

  /**
   * Constants for package/module state.
   */
  const STATE_DEFAULT = 0;
  const STATE_OVERRIDDEN = 1;

  /**
   * Returns the active config store.
   *
   * @return \Drupal\Core\Config\StorageInterface
   */
  public function getActiveStorage();

  /**
   * Returns a set of config storages.
   *
   * This method is used for support of multiple extension configuration
   * directories, including the core-provided install and optional directories.
   *
   * @return \Drupal\Core\Config\StorageInterface[]
   */
  public function getExtensionStorages();

  /**
   * Resets packages and configuration assignment.
   */
  public function reset();

  /**
   * Gets an array of site configuration.
   *
   * @param bool $reset
   *   If TRUE, recalculate the configuration (undo all assignment methods).
   *
   * @return \Drupal\features\ConfigurationItem[]
   *   An array of items, each with the following keys:
   *   - 'name': prefixed configuration item name.
   *   - 'name_short': configuration item name without prefix.
   *   - 'label': human readable name of configuration item.
   *   - 'type': type of configuration.
   *   - 'data': the contents of the configuration item in exported format.
   *   - 'dependents': array of names of dependent configuration items.
   *   - 'subdirectory': feature subdirectory to export item to.
   *   - 'package': machine name of a package the configuration is assigned to.
   *   - 'extension_provided': whether the configuration is provided by an
   *     extension.
   *   - 'package_excluded': array of package names that this item should be
   *     excluded from.
   */
  public function getConfigCollection($reset = FALSE);

  /**
   * Sets an array of site configuration.
   *
   * @param \Drupal\features\ConfigurationItem[] $config_collection
   *   An array of items.
   */
  public function setConfigCollection(array $config_collection);

  /**
   * Gets an array of packages.
   *
   * @return \Drupal\features\Package[]
   *   An array of items, each with the following keys:
   *   - 'machine_name': machine name of the package such as 'example_article'.
   *     'article'.
   *   - 'name': human readable name of the package such as 'Example Article'.
   *   - 'description': description of the package.
   *   - 'type': type of Drupal project ('module').
   *   - 'core': Drupal core compatibility ('8.x').
   *   - 'dependencies': array of module dependencies.
   *   - 'themes': array of names of themes to install.
   *   - 'config': array of names of configuration items.
   *   - 'status': status of the package. Valid values are:
   *      - FeaturesManagerInterface::STATUS_NO_EXPORT
   *      - FeaturesManagerInterface::STATUS_INSTALLED
   *      - FeaturesManagerInterface::STATUS_UNINSTALLED
   *   - 'version': version of the extension.
   *   - 'state': state of the extension. Valid values are:
   *      - FeaturesManagerInterface::STATE_DEFAULT
   *      - FeaturesManagerInterface::STATE_OVERRIDDEN
   *   - 'directory': the extension's directory.
   *   - 'files' array of files, each having the following keys:
   *      - 'filename': the name of the file.
   *      - 'subdirectory': any subdirectory of the file within the extension
   *         directory.
   *      - 'string': the contents of the file.
   *   - 'bundle': name of the features bundle this package belongs to.
   *   - 'extension': \Drupal\Core\Extension\Extension object.
   *   - 'info': the original info array from an existing package.
   *   - 'config_info': the original config of the module.
   *
   * @see \Drupal\features\FeaturesManagerInterface::setPackages()
   */
  public function getPackages();

  /**
   * Sets an array of packages.
   *
   * @param \Drupal\features\Package[] $packages
   *   An array of packages.
   */
  public function setPackages(array $packages);

  /**
   * Gets a specific package.
   *
   * @param string $machine_name
   *   Full machine name of package.
   *
   * @return \Drupal\features\Package
   *   Package data.
   *
   * @see \Drupal\features\FeaturesManagerInterface::getPackages()
   */
  public function getPackage($machine_name);

  /**
   * Gets a specific package.
   * Similar to getPackage but will also match package FullName
   *
   * @param string $machine_name
   *   Full machine name of package.
   *
   * @return \Drupal\features\Package
   *   Package data.
   *
   * @see \Drupal\features\FeaturesManagerInterface::getPackages()
   */
  public function findPackage($machine_name);

  /**
   * Updates a package definition in the package list.
   *
   * NOTE: This does not "export" the package; it simply updates the internal
   * data.
   *
   * @param \Drupal\features\Package $package
   *   The package.
   */
  public function setPackage(Package $package);

  /**
   * Filters the supplied package list by the given namespace.
   *
   * @param \Drupal\features\Package[] $packages
   *   An array of packages.
   * @param string $namespace
   *   The namespace to use.
   * @param bool $only_exported
   *   If true, only filter out packages that are exported
   *
   * @return \Drupal\features\Package[]
   *   An array of packages.
   */
  public function filterPackages(array $packages, $namespace = '', $only_exported = FALSE);

  /**
   * Gets a reference to a package assigner.
   *
   * @return \Drupal\features\FeaturesAssignerInterface
   *   The package assigner.
   */
  public function getAssigner();

  /**
   * Injects the package assigner.
   *
   * @param \Drupal\features\FeaturesAssignerInterface $assigner
   *   The package assigner.
   */
  public function setAssigner(FeaturesAssignerInterface $assigner);

  /**
   * Gets a reference to a package generator.
   *
   * @return \Drupal\features\FeaturesGeneratorInterface
   *   The package generator.
   */
  public function getGenerator();

  /**
   * Injects the package generator.
   *
   * @param \Drupal\features\FeaturesGeneratorInterface $generator
   *   The package generator.
   */
  public function setGenerator(FeaturesGeneratorInterface $generator);

  /**
   * Returns the current export settings.
   *
   * @return array
   *   An array with the following keys:
   *   - 'folder' - subdirectory to export packages to.
   *   - 'namespace' - module namespace being exported.
   */
  public function getExportSettings();

  /**
   * Returns the current general features settings.
   *
   * @return \Drupal\Core\Config\Config
   *   A config object containing settings.
   */
  public function getSettings();

  /**
   * Returns the contents of an extensions info.yml file.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   An Extension object.
   *
   * @return array
   *   An array representing data in an info.yml file.
   */
  public function getExtensionInfo(Extension $extension);

  /**
   * Determine if extension is enabled
   *
   * @param \Drupal\Core\Extension\Extension $extension
   * @return bool
   */
  public function extensionEnabled(Extension $extension);

  /**
   * Initializes a configuration package.
   *
   * @param string $machine_name
   *   Machine name of the package.
   * @param string $name
   *   (optional) Human readable name of the package.
   * @param string $description
   *   (optional) Description of the package.
   * @param string $type
   *   (optional) Type of project.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   (optional) Bundle to use to add profile directories to the scan.
   * @param \Drupal\Core\Extension\Extension $extension
   *   (optional) An Extension object.
   * @return array
   *   The created package array.
   */
  public function initPackage($machine_name, $name = NULL, $description = '', $type = 'module', FeaturesBundleInterface $bundle = NULL, Extension $extension = NULL);

  /**
   * Initializes a configuration package using module info data.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   An Extension object.
   *
   * @return \Drupal\features\Package
   *   The created package array.
   */
  public function initPackageFromExtension(Extension $extension);

  /**
   * Lists directories in which packages are present.
   *
   * This method scans to find package modules whether or not they are
   * currently active (installed). As well as the directories that are
   * usually scanned for modules and profiles, a profile directory for the
   * current profile is scanned if it exists. For example, if the value
   * for $bundle->getProfileName() is 'example', a
   * directory profiles/example will be scanned if it exists. Therefore, when
   * regenerating package modules, existing ones from a prior export will be
   * recognized.
   *
   * @param string[] $machine_names
   *   Package machine names to return directories for. If omitted, return all
   *   directories.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   Optional bundle to use to add profile directories to the scan.
   *
   * @return array
   *   Array of package directories keyed by package machine name.
   */
  public function listPackageDirectories(array $machine_names = array(), FeaturesBundleInterface $bundle = NULL);

  /**
   * Assigns a set of configuration items to a given package or profile.
   *
   * @param string $package_name
   *   Machine name of a package or the profile.
   * @param string[] $item_names
   *   Configuration item names.
   * @param bool $force
   *   (optional) If TRUE, assign config regardless of restrictions such as it
   *   being already assigned to a package.
   *
   * @throws Exception
   */
  public function assignConfigPackage($package_name, array $item_names, $force = FALSE);

  /**
   * Assigns configuration items with names matching given strings to given
   * packages.
   *
   * @param array $patterns
   *   Array with string patterns as keys and package machine names as values.
   */
  public function assignConfigByPattern(array $patterns);

  /**
   * For given configuration items, assigns any dependent configuration to the
   * same package.
   *
   * @param string[] $item_names
   *   Configuration item names.
   * @param string $package
   *   Short machine name of package to assign dependent config to. If NULL,
   *   use the current package of the parent config items.
   */
  public function assignConfigDependents(array $item_names = NULL, $package = NULL);

  /**
   * Adds the optional bundle prefix to package machine names.
   *
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   The bundle used for the generation.
   * @param string[] &$package_names
   *   (optional) Array of package names, passed by reference.
   */
  public function setPackageBundleNames(FeaturesBundleInterface $bundle, array &$package_names = []);

  /**
   * Assigns dependencies from config items into the package.
   *
   * @param \Drupal\features\Package[] $packages
   *   An array of packages. NULL for all packages
   */
  public function assignPackageDependencies(Package $package = NULL);

  /**
   * Assigns dependencies between packages based on configuration dependencies.
   *
   * \Drupal\features\FeaturesBundleInterface::setPackageBundleNames() must be
   * called prior to calling this method.
   *
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   A features bundle.
   * @param \Drupal\features\Package[] $packages
   *   An array of packages.
   */
  public function assignInterPackageDependencies(FeaturesBundleInterface $bundle, array &$packages);

  /**
   * Merges two info arrays and processes the resulting array.
   *
   * Ensures values are unique and sorted.
   *
   * @param array $info1
   *   The first array.
   * @param array $info2
   *   The second array.
   * @param string[] $keys
   *   Keys to merge. If not specified, all keys present will be merged.
   *
   * @return array
   *   An array with the merged and processed results.
   *
   * @fixme Should this be moved to the package object or a related helper?
   */
  public function mergeInfoArray(array $info1, array $info2, array $keys = array());

  /**
   * Lists the types of configuration available on the site.
   *
   * @param boolean $bundles_only
   *   Whether to list only configuration types that provide bundles.
   *
   * @return array
   *   An array with machine name keys and human readable values.
   */
  public function listConfigTypes($bundles_only = FALSE);

  /**
   * Lists stored configuration for a given configuration type.
   *
   * @param string $config_type
   *   The type of configuration.
   */
  public function listConfigByType($config_type);

  /**
   * Returns a list of all modules present on the site's file system.
   *
   * @return Drupal\Core\Extension\Extension[]
   *   An array of extension objects.
   */
  public function getAllModules();

  /**
   * Returns a list of Features modules regardless of if they are installed.
   *
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   Optional bundle to filter module list.
   *   If given, only modules matching the bundle namespace will be returned.
   *   If the bundle uses a profile, only modules in the profile will be
   *   returned.
   * @param bool $installed
   *   List only installed modules.
   *
   * @return Drupal\Core\Extension\Extension[]
   *   An array of extension objects.
   */
  public function getFeaturesModules(FeaturesBundleInterface $bundle = NULL, $installed = FALSE);

  /**
   * Lists names of configuration objects provided by a given extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   An Extension object.
   *
   * @return array
   *   An array of configuration object names.
   */
  public function listExtensionConfig(Extension $extension);

  /**
   * Lists names of configuration items provided by existing Features modules.
   *
   * @param bool $installed
   *   List only installed Features.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   (optional) Bundle to find existing configuration for.
   *
   * @return array
   *   An array with config names as keys and providing module names as values.
   */
  public function listExistingConfig($installed = FALSE, FeaturesBundleInterface $bundle = NULL);

  /**
   * Iterates through packages and prepares file names and contents.
   *
   * @param array $packages
   *   An array of packages.
   */
  public function prepareFiles(array $packages);

  /**
   * Returns the full name of a config item.
   *
   * @param string $type
   *   The config type, or '' to indicate $name is already prefixed.
   * @param string $name
   *   The config name, without prefix.
   *
   * @return string
   *   The config item's full name.
   */
  public function getFullName($type, $name);

  /**
   * Returns the short name and type of a full config name.
   *
   * @param string $fullname
   *   The full configuration name
   * @return array
   *   'type' => string the config type
   *   'name_short' => string the short config name, without prefix.
   */
  public function getConfigType($fullname);

  /**
   * Returns the full machine name and directory for exporting a package.
   *
   * @param \Drupal\features\Package $package
   *   The package.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   Optional bundle being used for export.
   *
   * @return array
   *   An array with the full name as the first item and directory as second
   *   item.
   */
  public function getExportInfo(Package $package, FeaturesBundleInterface $bundle = NULL);

  /**
   * Determines if the module is a Features package, optinally testing by
   * bundle.
   *
   * @param \Drupal\Core\Extension\Extension $module
   *   An extension object.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   (optional) Bundle to filter by.
   *
   * @return bool
   *   TRUE if the given module is a Features package of the given bundle (if any).
   */
  public function isFeatureModule(Extension $module, FeaturesBundleInterface $bundle);

  /**
   * Determines which config is overridden in a package.
   *
   * @param \Drupal\features\Package $feature
   *   The package array.
   *   The 'state' property is updated if overrides are detected.
   * @param bool $include_new
   *   If set, include newly detected config not yet exported.
   *
   * @result array $different
   *   The array of config items that are overridden.
   *
   * @see \Drupal\features\FeaturesManagerInterface::detectNew()
   */
  public function detectOverrides(Package $feature, $include_new = FALSE);

  /**
   * Determines which config has not been exported to the feature.
   *
   * Typically added as an auto-detected dependency.
   *
   * @param \Drupal\features\Package $feature
   *   The package array.
   *
   * @return array
   *   The array of config items that are overridden.
   */
  public function detectNew(Package $feature);

  /**
   * Determines which config is exported in the feature but not in the active.
   *
   * @param \Drupal\features\Package $feature
   *   The package array.
   *
   * @return array
   *   The array of config items that are missing from active store.
   */
  public function detectMissing(Package $feature);

  /**
   * Sort the Missing config into order by dependencies.
   * @param array $missing config items
   * @return array of config items in dependency order
   */
  public function reorderMissing(array $missing);

  /**
   * Helper function that returns a translatable label for the different status
   * constants.
   *
   * @param int $status
   *   A status constant.
   *
   * @return string
   *   A translatable label.
   */
  public function statusLabel($status);

  /**
   * Helper function that returns a translatable label for the different state
   * constants.
   *
   * @param int $state
   *   A state constant.
   *
   * @return string
   *   A translatable label.
   */
  public function stateLabel($state);

  /**
   * @param \Drupal\Core\Extension\Extension $extension
   *
   * @return array
   */
  public function getFeaturesInfo(Extension $extension);

}

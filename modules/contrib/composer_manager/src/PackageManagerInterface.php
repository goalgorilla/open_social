<?php

/**
 * @file
 * Contains \Drupal\composer_manager\PackageManagerInterface.
 */

namespace Drupal\composer_manager;

/**
 * Provides an interface for managing composer packages.
 */
interface PackageManagerInterface {

  /**
   * Returns the core package.
   *
   * @return array
   *   The core package, loaded from core/composer.json.
   */
  public function getCorePackage();

  /**
   * Returns the extension packages.
   *
   * The composer.json file of each extension (module, profile) under each site
   * is loaded and returned.
   *
   * @return array
   *   An array of packages, keyed by the providing Drupal extension name.
   *
   * @see \Drupal\Core\Extension\ExtensionDiscovery
   */
  public function getExtensionPackages();

  /**
   * Returns the installed packages.
   *
   * @return array
   *   The installed packages, loaded from vendor/composer/installed.json.
   */
  public function getInstalledPackages();

  /**
   * Returns the required packages.
   *
   * This includes all extension requirements as well as any previously
   * installed packages that are no longer required.
   * The core requirements are not listed, for brevity.
   *
   * @return array
   *   An array of packages, keyed by package name, with the following keys:
   *   - constraint: The imposed version constraint (e.g. '>=2.7').
   *   - description: Package description, if known.
   *   - homepage: Package homepage, if known.
   *   - require: Package requirements, if known.
   *   - required_by: An array of dependent package names. Empty if the package
   *     is no longer required.
   *   - version: The installed package version. Empty if the package hasn't
   *     been installed yet.
   */
  public function getRequiredPackages();

  /**
   * Returns whether a composer update is needed.
   *
   * An update is needed when there are packages that are:
   * 1. Required, but not installed.
   * 2. Installed, but no longer required.
   *
   * @return bool
   *   True if a composer update is needed, false otherwise.
   */
  public function needsComposerUpdate();

  /**
   * Rebuilds the root package by merging in the extension requirements.
   */
  public function rebuildRootPackage();

}

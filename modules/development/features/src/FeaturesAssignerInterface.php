<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesAssignerInterface.
 */

namespace Drupal\features;

/**
 * Common interface for features assignment services.
 *
 * The feature API is based on two major concepts:
 * - Packages: modules into which configuration is packaged.
 * - Package assignment methods: responsible for `determining
 *   which package to assign a given piece of configuration to.
 * Assignment methods are customizable.
 *
 * Features defines several package assignment methods, which are simple plugin
 * classes that implement a particular logic to assign pieces of configuration
 * to a given package (module).
 *
 * Modules can define additional package assignment methods by simply providing
 * the related plugins, and alter existing methods through
 * hook_features_assignment_method_info_alter(). Here is an example
 * snippet:
 * @code
 * function mymodule_features_assignment_method_info_alter(&$assignment_info) {
 *   // Replace the original plugin with our own implementation.
 *   $method_id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentBaseType::METHOD_ID;
 *   $assignment_info[$method_id]['class'] = 'Drupal\my_module\Plugin\FeaturesAssignment\MyFeaturesAssignmentBaseType';
 * }
 *
 * class MyFeaturesAssignmentBaseType extends FeaturesAssignmentBaseType {
 *   public function assignPackages($force = FALSE) {
 *     // Insert customization here.
 *   }
 * }
 * ?>
 * @endcode
 *
 * For more information, see
 * @link http://drupal.org/node/2404473 Developing for Features 3.x @endlink
 */
interface FeaturesAssignerInterface {

  /**
   * The package assignment method id for the package assigner itself.
   */
  const METHOD_ID = 'assigner-default';

  /**
   * Resets the assigned packages and the method instances.
   */
  public function reset();

  /**
   * Apply all enabled package assignment methods.
   *
   * @param bool $force
   *   (optional) If TRUE, assign config regardless of restrictions such as it
   *   being already assigned to a package.
   */
  public function assignConfigPackages($force = FALSE);

  /**
   * Applies a given package assignment method.
   *
   * @param string $method_id
   *   The string identifier of the package assignment method to use to package
   *   configuration.
   * @param bool $force
   *   (optional) If TRUE, assign config regardless of restrictions such as it
   *   being already assigned to a package.
   */
  public function applyAssignmentMethod($method_id, $force = FALSE);

  /**
   * Returns the enabled package assignment methods.
   *
   * @return array
   *   An array of package assignment method IDs.
   */
  public function getAssignmentMethods();

  /**
   * Resaves the configuration to purge missing assignment methods.
   */
  public function purgeConfiguration();

  /**
   * Returns a FeaturesBundle object.
   *
   * @param string $name
   *   machine name of package set. If omitted, returns the current bundle.
   *
   * @return \Drupal\features\FeaturesBundleInterface
   *   A features bundle object.
   */
  public function getBundle($name = NULL);

  /**
   * Stores a features bundle.
   *
   * Added to list if machine_name is new.
   *
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   A features bundle.
   * @param bool $current
   *   Determine if the current bundle is set to $bundle.
   *   If False, the current bundle is only updated if it already has the same
   *   machine name as the $bundle.
   */
  public function setBundle(FeaturesBundleInterface $bundle, $current = TRUE);

  /**
   * Searches for a bundle that matches the $info.yml or $features.yml export.
   *
   * Creates a new bundle as needed.
   *
   * @param array $info
   *   The bundle info.
   * @param mixed $features_info
   *   The features info.
   *
   * @return \Drupal\features\FeaturesBundleInterface
   *   A bundle.
   */
  public function findBundle(array $info, $features_info = NULL);

  /**
   * Sets the currently active bundle.
   *
   * Updates value in current SESSION.
   *
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   A features bundle object.
   */
  public function setCurrent(FeaturesBundleInterface $bundle);

  /**
   * Returns an array of all existing features bundles.
   *
   * @return \Drupal\features\FeaturesBundleInterface[]
   *   Keyed by machine_name with value of
   *   \Drupal\features\FeaturesBundleInterface.
   */
  public function getBundleList();

  /**
   * Returns a named bundle.
   *
   * First searches by Human name, then by machine_name.
   *
   * @param string $name
   *   The bundle name to search by.
   *
   * @return \Drupal\features\FeaturesBundleInterface
   *   A features bundle object.
   */
  public function findBundleByName($name);

  /**
   * Creates a new bundle by duplicating the default bundle and customizing.
   *
   * @param string $machine_name
   *   Machine name.
   * @param string $name
   *   (optional) Human readable name of the bundle.
   * @param string $description
   *   (optional) Description of the bundle.
   * @param bool $is_profile
   *   (optional) TRUE if a profile is used with this bundle.
   * @param string $profile_name
   *   (optional) The machine name of the profile.
   *
   * @return \Drupal\features\FeaturesBundleInterface
   *   A features bundle object.
   */
  public function createBundleFromDefault($name, $machine_name = '', $description = '', $is_profile = FALSE, $profile_name = NULL);

  /**
   * Creates bundles by parsing information from installed packages.
   */
  public function createBundlesFromPackages();

  /**
   * Returns an array of bundle names suitable for a select option list.
   *
   * @return array
   *   An array of bundles, keyed by machine_name, with values being human
   *   readable names.
   */
  public function getBundleOptions();

  /**
   * Makes the named bundle the current bundle.
   *
   * @param string $machine_name
   *   The name of a features bundle. If omitted, gets the last bundle from the
   *   Session.
   *
   * @return \Drupal\features\FeaturesBundleInterface
   *   A features bundle object.
   */
  public function applyBundle($machine_name = NULL);

  /**
   * Renames a bundle.
   *
   * @param string $old_machine
   *   The old machine name of a bundle.
   * @param string $new_machine
   *   The new machine name of a bundle.
   *
   * @return \Drupal\features\FeaturesBundleInterface
   *   A features bundle object.
   */
  public function renameBundle($old_machine, $new_machine);

  /**
   * Loads a named bundle.
   *
   * @param string $machine_name
   *   (optional) The name of a features bundle.
   *   Defaults to NULL, gets the last bundle from the session.
   *
   * @return \Drupal\features\FeaturesBundleInterface
   *   A features bundle object.
   */
  public function loadBundle($machine_name = NULL);

}

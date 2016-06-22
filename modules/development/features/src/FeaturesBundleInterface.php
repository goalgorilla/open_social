<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesBundleInterface.
 */

namespace Drupal\features;

/**
 * Provides an interface for the FeaturesBundle object.
 */
interface FeaturesBundleInterface {

  const DEFAULT_BUNDLE = 'default';

  /**
   * Determines whether the current bundle is the default one.
   *
   * @return bool
   *   Returns TRUE if this is the default bundle.
   */
  public function isDefault();

  /**
   * Returns the machine name of a bundle.
   *
   * @return string
   *   The machine name of a bundle.
   *
   * @see \Drupal\features\FeaturesBundleInterface::setMachineName()
   */
  public function getMachineName();

  /**
   * Sets the machine name of a bundle.
   *
   * @param string $machine_name
   *   The machine name of a bundle.
   *
   * @see \Drupal\features\FeaturesBundleInterface::getMachineName()
   */
  public function setMachineName($machine_name);

  /**
   * Gets the human readable name of a bundle.
   *
   * @return string
   *   The human readable name of a bundle.
   *
   * @see \Drupal\features\FeaturesBundleInterface::setName()
   */
  public function getName();

  /**
   * Sets the human readable name of a bundle.
   *
   * @param string $name
   *   The human readable name of a bundle.
   *
   * @see \Drupal\features\FeaturesBundleInterface::getName()
   */
  public function setName($name);

  /**
   * Returns a full machine name prefixed with the bundle name.
   *
   * @param string $short_name
   *   The short machine_name of a bundle.
   *
   * @return string
   *   The full machine_name of a bundle.
   */
  public function getFullName($short_name);

  /**
   * Returns a short machine name not prefixed with the bundle name.
   *
   * @param string $machine_name
   *   The full machine_name of a bundle.
   *
   * @return string
   *   The short machine_name of a bundle.
   */
  public function getShortName($machine_name);

  /**
   * Determines if the $machine_name is prefixed by the bundle machine name.
   *
   * @param string $machine_name
   *   The machine name of a package.
   *
   * @return bool
   *   TRUE if the machine name is prefixed by the bundle machine name.
   */
  public function inBundle($machine_name);

  /**
   * Determines if the package with $machine_name is the bundle profile.
   *
   * @param string $machine_name
   *   The machine name of a package.
   *
   * @return bool
   *   TRUE if the package with $machine_name is the bundle profile.
   */
  public function isProfilePackage($machine_name);

  /**
   * Gets the description of a bundle.
   *
   * @return string
   *   The description of a bundle.
   *
   * @see \Drupal\features\FeaturesBundleInterface::setDescription()
   */
  public function getDescription();

  /**
   * Sets the description of a bundle.
   *
   * @param string $description
   *   The description of a bundle.
   *
   * @see \Drupal\features\FeaturesBundleInterface::getDescription()
   */
  public function setDescription($description);

  /**
   * Gets option for using a profile with this bundle.
   *
   * @return bool
   *   TRUE if a profile is used with this profile.
   */
  public function isProfile();

  /**
   * Sets option for using a profile with this bundle.
   *
   * @param bool $value
   *   TRUE if a profile is used with this bundle.
   */
  public function setIsProfile($value);

  /**
   * Returns the machine name of the profile.
   *
   * If the bundle doesn't use a profile, return the current site profile.
   *
   * @return string
   *   THe machie name of a profile.
   *
   * @see \Drupal\features\FeaturesBundleInterface::setProfileName()
   */
  public function getProfileName();

  /**
   * Sets the name of the profile associated with this bundle.
   *
   * @param string $machine_name
   *   The machine name of a profile.
   *
   * @see \Drupal\features\FeaturesBundleInterface::getProfileName()
   */
  public function setProfileName($machine_name);

  /**
   * Gets the list of enabled assignment methods.
   *
   * @return array
   *   An array of method IDs keyed by assignment method IDs.
   *
   * @see \Drupal\features\FeaturesBundleInterface::setEnabledAssignments()
   */
  public function getEnabledAssignments();

  /**
   * Sets the list of enabled assignment methods.
   *
   * @param array $assignments
   *   An array of values keyed by assignment method IDs. Non-empty value is
   *   enabled.
   *
   * @see \Drupal\features\FeaturesBundleInterface::getEnabledAssignments()
   */
  public function setEnabledAssignments(array $assignments);

  /**
   * Gets the weights of the assignment methods.
   *
   * @return array
   *   An array keyed by assignment method_id with a numeric weight.
   *
   * @see \Drupal\features\FeaturesBundleInterface::setAssignmentWeights()
   */
  public function getAssignmentWeights();

  /**
   * Sets the weights of the assignment methods.
   *
   * @param array $assignments
   *   An array keyed by assignment method_id with a numeric weight value.
   *
   * @see \Drupal\features\FeaturesBundleInterface::getAssignmentWeights()
   */
  public function setAssignmentWeights(array $assignments);

  /**
   * Gets settings specific to an assignment method.
   *
   * @param string $method_id
   *   The ID of an assignment method. If NULL, return all assignment settings
   *   keyed by method_id.
   *
   * @return array
   *   An array of settings. Format specific to assignment method.
   *
   * @see \Drupal\features\FeaturesBundleInterface::setAssignmentSettings()
   */
  public function getAssignmentSettings($method_id = NULL);

  /**
   * Sets settings specific to an assignment method.
   *
   * @param string $method_id
   *   The ID of an assignment method. If NULL, all $settings are given keyed
   *   by method_ID.
   * @param array $settings
   *   An array of setting values.
   *
   * @see \Drupal\features\FeaturesBundleInterface::getAssignmentSettings()
   */
  public function setAssignmentSettings($method_id, array $settings);

  /**
   * Saves the bundle to the active config.
   */
  public function save();

  /**
   * Removes the bundle from the active config.
   */
  public function remove();

}

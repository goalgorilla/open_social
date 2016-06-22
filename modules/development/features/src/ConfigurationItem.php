<?php

/**
 * @file
 * Contains \Drupal\features\ConfigurationItem.
 */

namespace Drupal\features;

/**
 * Contains some configuration together with metadata like the name + package.
 *
 * @todo Should the object be immutable?
 * @todo Should this object have an interface?
 */
class ConfigurationItem {

  /**
   * Prefixed configuration item name.
   *
   * @var string
   */
  protected $name;

  /**
   * Configuration item name without prefix.
   *
   * @var string
   */
  protected $shortName;

  /**
   * Human readable name of configuration item.
   *
   * @var string
   */
  protected $label;

  /**
   * Type of configuration.
   *
   * @var string
   */
  protected $type;

  /**
   * The contents of the configuration item in exported format.
   *
   * @var array
   */
  protected $data;

  /**
   * Array of names of dependent configuration items.
   *
   * @var string[]
   */
  protected $dependents = [];

  /**
   * Feature subdirectory to export item to.
   *
   * @var string
   */
  protected $subdirectory;

  /**
   * Machine name of a package the configuration is assigned to.
   *
   * @var string
   */
  protected $package;

  /**
   * Whether the configuration is marked as excluded.
   *
   * @var bool
   */
  protected $excluded = FALSE;

  /**
   * Whether the configuration provider is excluded.
   *
   * @var bool
   */
  protected $providerExcluded = FALSE;

  /**
   * The provider of the config item.
   *
   * @var string
   */
  protected $provider;

  /**
   * Array of package names that this item should be excluded from.
   *
   * @var string[]
   */
  protected $packageExcluded = [];

  /**
   * Creates a new ConfigurationItem instance.
   *
   * @param string $name
   *   The config name.
   * @param array $data
   *   The config data.
   * @param array $additional_properties
   *   (optional) Additional properties set on the object.
   */
  public function __construct($name, array $data, array $additional_properties = []) {
    $this->name = $name;
    $this->data = $data;

    $properties = get_object_vars($this);
    foreach ($additional_properties as $property => $value) {
      if (!array_key_exists($property, $properties)) {
        throw new \InvalidArgumentException('Invalid property: ' . $property);
      }
      $this->{$property} = $value;
    }
  }

  /**
   * Calculates the config type usable in configuration.
   *
   * By default Drupal uses system.simple as config type, which cannot be used
   * inside configuration itself. Therefore convert it to system_simple.
   *
   * @param string $type
   *   The config type provided by core.
   *
   * @return string
   *   The config type as string without dots.
   */
  public static function fromConfigTypeToConfigString($type) {
    return $type == 'system.simple' ? FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG : $type;
  }

  /**
   * Converts a config type string in configuration back to the config type.
   *
   * @param string $type
   *   The config type as string without dots.
   *
   * @return string
   *   The config type provided by core.
   */
  public static function fromConfigStringToConfigType($type) {
    return $type == FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG ? 'system.simple' : $type;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param mixed $name
   *
   * @return ConfigurationItem
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getShortName() {
    return $this->shortName;
  }

  /**
   * @param mixed $shortName
   *
   * @return ConfigurationItem
   */
  public function setShortName($shortName) {
    $this->shortName = $shortName;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @param mixed $label
   *
   * @return ConfigurationItem
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @param mixed $type
   *
   * @return ConfigurationItem
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getData() {
    return $this->data;
  }

  /**
   * @param mixed array
   *
   * @return ConfigurationItem
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * @return string[]
   */
  public function getDependents() {
    return $this->dependents;
  }

  /**
   * @param array $dependents
   *
   * @return ConfigurationItem
   */
  public function setDependents($dependents) {
    $this->dependents = $dependents;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getSubdirectory() {
    return $this->subdirectory;
  }

  /**
   * @param mixed $subdirectory
   *
   * @return ConfigurationItem
   */
  public function setSubdirectory($subdirectory) {
    $this->subdirectory = $subdirectory;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getPackage() {
    return $this->package;
  }

  /**
   * @param mixed $package
   *
   * @return ConfigurationItem
   */
  public function setPackage($package) {
    $this->package = $package;
    return $this;
  }

  /**
   * @return boolean
   */
  public function isExcluded() {
    return $this->excluded;
  }

  /**
   * @param boolean $excluded
   *
   * @return ConfigurationItem
   */
  public function setExcluded($excluded) {
    $this->excluded = $excluded;
    return $this;
  }

  /**
   * @return boolean
   */
  public function isProviderExcluded() {
    return $this->providerExcluded;
  }

  /**
   * @param boolean $providerExcluded
   *
   * @return ConfigurationItem
   */
  public function setProviderExcluded($providerExcluded) {
    $this->providerExcluded = $providerExcluded;
    return $this;
  }

  /**
   * @return string
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * @param string $provider
   */
  public function setProvider($provider) {
    $this->provider = $provider;
    return $this;
  }

  /**
   * @return string[]
   */
  public function getPackageExcluded() {
    return $this->packageExcluded;
  }

  /**
   * @param array $packageExcluded
   *
   * @return ConfigurationItem
   */
  public function setPackageExcluded($packageExcluded) {
    $this->packageExcluded = $packageExcluded;
    return $this;
  }

}

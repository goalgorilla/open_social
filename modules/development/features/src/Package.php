<?php

namespace Drupal\features;

/**
 * Defines a value object for storing package related data.
 *
 * A package contains of a name, version number, containing config etc.
 */
class Package {

  /**
   * @var string
   */
  protected $machineName = '';

  /**
   * @var string
   */
  protected $name = '';

  /**
   * @var string
   */
  protected $description = '';

  /**
   * @todo This could be fetched from the extension object.
   *
   * @var string
   */
  protected $version = '';

  /**
   * @var string
   */
  protected $core = '8.x';

  /**
   * @todo This could be fetched from the extension object.
   *
   * @var string
   */
  protected $type = 'module';

  /**
   * @var string[]
   */
  protected $themes = [];

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string[]
   */
  protected $excluded = [];

  /**
   * @var string[]
   */
  protected $required = [];

  /**
   * @var array
   */
  protected $info = [];

  /**
   * @var string[]
   */
  protected $dependencies = [];

  /**
   * @todo This could be fetched from the extension object.
   *
   * @var int
   */
  protected $status;

  /**
   * @var int
   */
  protected $state;

  /**
   * @todo This could be fetched from the extension object.
   *
   * @var string
   */
  protected $directory;

  /**
   * @var string[]
   */
  protected $files;

  /**
   * @var \Drupal\Core\Extension\Extension
   */
  protected $extension;

  /**
   * @var string[]
   */
  protected $config = [];

  /**
   * @var string[]
   */
  protected $configOrig = [];

  /**
   * The features info.
   *
   * Contains the components used in this feature.
   *
   * @var array
   */
  protected $featuresInfo = [];

  /**
   * Creates a new Package instance.
   *
   * @param string $machine_name
   *   The machine name.
   * @param array $additional_properties
   *   (optional) Additional properties of the object.
   */
  public function __construct($machine_name, array $additional_properties = []) {
    $this->machineName = $machine_name;

    $properties = get_object_vars($this);
    foreach ($additional_properties as $property => $value) {
      if (!array_key_exists($property, $properties)) {
        throw new \InvalidArgumentException('Invalid property: ' . $property);
      }
      $this->{$property} = $value;
    }
  }

  /**
   * @return mixed
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * @return string
   */
  public function getFullName() {
    if (!empty($this->bundle)) {
      return $this->bundle . '_' . $this->machineName;
    }
    return $this->machineName;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @return string
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * @return int
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @return string[]
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Append a new filename.
   *
   * @param string $config
   *
   * @return $this
   */
  public function appendConfig($config) {
    $this->config[] = $config;
    $this->config = array_unique($this->config);
    return $this;
  }

  public function removeConfig($name) {
    $this->config = array_diff($this->config, [$name]);
    return $this;
  }

  /**
   * @return string
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * @return string[]
   */
  public function getExcluded() {
    return $this->excluded;
  }

  /**
   * @return string[]
   */
  public function getRequired() {
    return $this->required;
  }

  /**
   * @return bool
   */
  public function getRequiredAll() {
    $config_orig = $this->getConfigOrig();
    $info = isset($this->getFeaturesInfo()['required']) ? $this->getFeaturesInfo()['required'] : array();
    $info = is_array($info) ? $info : array();
    $diff = array_diff($config_orig, $info);
    // Mark all as required if required:true, or required is empty, or
    // if required contains all the exported config
    return empty($diff) || empty($info);
  }

  /**
   * @return string[]
   */
  public function getConfigOrig() {
    return $this->configOrig;
  }

  /**
   * @return string
   */
  public function getCore() {
    return $this->core;
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @return \string[]
   */
  public function getThemes() {
    return $this->themes;
  }

  /**
   * @return array
   */
  public function getInfo() {
    return $this->info;
  }

  /**
   * @return mixed
   */
  public function getState() {
    return $this->state;
  }

  /**
   * @return string
   */
  public function getDirectory() {
    return $this->directory;
  }

  /**
   * @return mixed
   */
  public function getFiles() {
    return $this->files;
  }

  /**
   * @return \Drupal\Core\Extension\Extension
   */
  public function getExtension() {
    return $this->extension;
  }

  public function getDependencies() {
    return $this->dependencies;
  }

  public function removeDependency($name) {
    $this->dependencies = array_diff($this->dependencies, [$name]);
    return $this;
  }

  public function getDependencyInfo() {
    return isset($this->info['dependencies']) ? $this->info['dependencies'] : [];
  }

  /**
   * Returns the features info.
   *
   * @return array
   */
  public function getFeaturesInfo() {
    return $this->featuresInfo;
  }

  /**
   * Sets a new machine name.
   *
   * @param string $machine_name
   *   The machine name
   *
   * @return $this
   */
  public function setMachineName($machine_name) {
    $this->machineName = $machine_name;
    return $this;
  }

  /**
   * @param string $name
   *
   * @return $this
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * @param string $version
   *
   * @return $this
   */
  public function setVersion($version) {
    $this->version = $version;
    return $this;
  }

  /**
   * @param string $bundle
   *
   * @return $this
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }

  /**
   * @param array $info
   *
   * @return $this
   */
  public function setInfo($info) {
    $this->info = $info;
    return $this;
  }

  /**
   * @param \string[] $features_info
   *
   * @return $this
   */
  public function setFeaturesInfo($features_info) {
    $this->featuresInfo = $features_info;
    if (isset($features_info['bundle'])) {
      $this->setBundle($features_info['bundle']);
    }

    return $this;
  }

  /**
   * @param \string[] $dependencies
   *
   * @return $this
   */
  public function setDependencies($dependencies) {
    $this->dependencies = $dependencies;
    return $this;
  }

  /**
   * @param string $dependency
   *
   * return $this
   */
  public function appendDependency($dependency) {
    $this->dependencies[] = $dependency;
    return $this;
  }

  /**
   * @param int $status
   *
   * @return $this
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * @param \string[] $config
   *
   * @return $this
   */
  public function setConfig($config) {
    $this->config = $config;
    return $this;
  }

  /**
   * @param bool $excluded
   */
  public function setExcluded($excluded) {
    $this->excluded = $excluded;
  }

  /**
   * @param bool $required
   */
  public function setRequired($required) {
    $this->required = $required;
  }

  /**
   * @param string $core
   */
  public function setCore($core) {
    $this->core = $core;
  }

  /**
   * @param string $type
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * @param \string[] $themes
   */
  public function setThemes($themes) {
    $this->themes = $themes;
  }

  /**
   * @param int $state
   */
  public function setState($state) {
    $this->state = $state;
  }

  /**
   * @param string $directory
   */
  public function setDirectory($directory) {
    $this->directory = $directory;
  }

  /**
   * @param \string[] $files
   */
  public function setFiles($files) {
    $this->files = $files;
  }

  /**
   * @param array $file_array
   *
   * @return $this
   */
  public function appendFile(array $file_array, $key = NULL) {
    if (!isset($key)) {
      $this->files[] = $file_array;
    }
    else {
      $this->files[$key] = $file_array;
    }
    return $this;
  }

  /**
   * @param \Drupal\Core\Extension\Extension $extension
   */
  public function setExtension($extension) {
    $this->extension = $extension;
  }

  /**
   * @param \string[] $configOrig
   */
  public function setConfigOrig($configOrig) {
    $this->configOrig = $configOrig;
  }

}

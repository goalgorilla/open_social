<?php

namespace Drupal\social\Installer;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Serialization\Yaml;
use Drupal\social\Exception\SocialFeatureDataException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Optional Module Manager.
 *
 * Used to discover extensions in Open Social that can be optionally enabled
 * during installation.
 */
class OptionalModuleManager implements ContainerInjectionInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The name of the active install profile.
   *
   * @var string
   */
  protected $installProfile;

  /**
   * Constructs a OptionalModuleManager instance.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param string $install_profile
   *   The name of the active install profile.
   */
  public function __construct(ModuleExtensionList $extension_list_module, string $install_profile) {
    $this->moduleExtensionList = $extension_list_module;
    $this->installProfile = $install_profile;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module'),
      $container->getParameter('install_profile')
    );
  }

  /**
   * Collects the optionally installable modules for Open Social.
   *
   * A module is optionally installable if it contains a
   * `modulename.social_feature.yml` file. The file should contain data about
   * the module and information to help a user decide whether it's needed.
   * The `default` key can be used to determine whether the feature is selected
   * by default (default: false) and `weight` can be used to arrange features in
   * a certain order, lower is higher (default: 0).
   *
   * Below is an example.
   * ```
   *   name: Search Autocomplete
   *   description: Show interactive search suggestions in the search overlay.
   *   default: true
   * ```
   *
   * @return array
   *   An array containing the information about the optional module keyed by
   *   module name.
   */
  public function getOptionalModules() : array {
    $optional_modules = [];
    $available_modules = $this->moduleExtensionList->getList();

    $install_profile_features = $this->getInstallProfileFeatureDefinitions();

    // Loop over each available module to check if it can be enabled as optional
    // Open Social feature.
    foreach ($available_modules as $name => $extension) {
      $module_info = $this->getOptionalModuleInfo($extension);
      // If the module has feature info, add it.
      if ($module_info !== NULL) {
        $optional_modules[$name] = $module_info;
      }
      // Otherwise see if the installation profile says that it can be a feature
      // and use that info.
      elseif (isset($install_profile_features[$name])) {
        $optional_modules[$name] = $install_profile_features[$name];
      }
    }

    return $optional_modules;
  }

  /**
   * Loads feature definitions from a `profile.feature_list.yml` in the profile.
   *
   * This allows optional features to be defined for modules where it's not
   * possible to add a `modulename.social_feature.yml` file to the module.
   *
   * @return array
   *   An array containing the extra feature definitions, keyed by module name.
   */
  protected function getInstallProfileFeatureDefinitions() : array {
    $feature_list_file = $this->moduleExtensionList->getPath($this->installProfile) . DIRECTORY_SEPARATOR . $this->installProfile . '.feature_list.yml';
    if (!file_exists($feature_list_file)) {
      return [];
    }

    $features = Yaml::decode(file_get_contents($feature_list_file));

    foreach ($features as $module_name => &$info) {
      // Validate the info so we know it won't cause any issues.
      $info += $this->getInfoDefaults();
      try {
        $this->validateSocialFeatureData($module_name, $info);
      }
      catch (SocialFeatureDataException $e) {
        throw new SocialFeatureDataException("Invalid feature info for '{$module_name}' in `social_feature_list.yml`.", 0, $e);
      }
    }

    return $features;
  }

  /**
   * Load the optional module info for the extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to get the info for.
   *
   * @return array|null
   *   An array with the info about the optional module or null if it's not an
   *   optional module.
   */
  protected function getOptionalModuleInfo(Extension $extension) : ?array {
    $module_directory = $extension->getPath();
    $optional_info_file = $module_directory . DIRECTORY_SEPARATOR . $extension->getName() . '.social_feature.yml';
    if (!file_exists($optional_info_file)) {
      return NULL;
    }

    // We don't catch the InvalidDataTypeException thrown here because we have
    // no better info to give developers about invalid Yaml files.
    $feature_info = Yaml::decode(file_get_contents($optional_info_file));

    // Add our defaults to the data.
    $feature_info += $this->getInfoDefaults();

    // Validate the data that we've found.
    if (!$this->validateSocialFeatureData($extension->getName(), $feature_info)) {
      return NULL;
    }

    return $feature_info;
  }

  /**
   * Validate the data for an Open Social optional feature.
   *
   * @param string $module_name
   *   The name of the module that's being validated for error messages.
   * @param array $info
   *   The array of info to validate.
   *
   * @throws \Drupal\social\Exception\SocialFeatureDataException
   *   An exception that indicates what's wrong with the data.
   *
   * @return true
   *   Returns TRUE when the data is valid.
   */
  protected function validateSocialFeatureData(string $module_name, array $info) : bool {
    if (!isset($info['name'])) {
      throw new SocialFeatureDataException("Missing `name` field in `{$module_name}.social_feature.yml`.");
    }

    if (!isset($info['description'])) {
      throw new SocialFeatureDataException("Missing `description` field in `{$module_name}.social_feature.yml`.");
    }

    if (!is_bool($info['default'])) {
      throw new SocialFeatureDataException("Field `default` must be of type `bool` in `{$module_name}.social_feature.yml`.");
    }

    if (!is_int($info['weight'])) {
      throw new SocialFeatureDataException("Field `weight` must be of type `int` in `{$module_name}.social_feature.yml`.");
    }

    return TRUE;
  }

  /**
   * Get the default values for optional feature info parameters.
   *
   * @return array
   *   The default values for optional feature info parameters.
   */
  private function getInfoDefaults() : array {
    return [
      'default' => FALSE,
      'weight' => 0,
    ];
  }

}

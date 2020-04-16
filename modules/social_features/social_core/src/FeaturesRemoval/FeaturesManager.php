<?php
// phpcs:ignoreFile

namespace Drupal\social_core\FeaturesRemoval;

use Drupal\config_update\ConfigRevertInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\features\ConfigurationItem;
use Drupal\features\FeaturesBundleInterface;
use Drupal\features\FeaturesManager as FeaturesManagerBase;
use Drupal\features\FeaturesManagerInterface;

/**
 * Overrides the features FeaturesManager.
 *
 * Changes all `config/install` to `config/features_removal`.
 */
class FeaturesManager extends FeaturesManagerBase {

  public function __construct($root, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory,
                              StorageInterface $config_storage, ConfigManagerInterface $config_manager,
                              ModuleHandlerInterface $module_handler, ConfigRevertInterface $config_reverter) {
    parent::__construct($root, $entity_type_manager, $config_factory, $config_storage, $config_manager, $module_handler, $config_reverter);
    $this->extensionStorages = new FeaturesExtensionStoragesByDirectory($this->configStorage);
    $this->extensionStorages->addStorage(InstallStorage::CONFIG_INSTALL_DIRECTORY);
    $this->extensionStorages->addStorage(InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
  }

  protected function getConfigDependency(ConfigurationItem $config, $module_list = []) {
    $dependencies = [];
    $type = $config->getType();

    // For configuration in the InstallStorage::CONFIG_INSTALL_DIRECTORY
    // directory, set any dependencies of the configuration item as package
    // dependencies.
    // As its name implies, the core-provided
    // InstallStorage::CONFIG_OPTIONAL_DIRECTORY should not create
    // dependencies.
    // @phpcs:
//    if ($config->getSubdirectory() === InstallStorage::CONFIG_INSTALL_DIRECTORY) {
    if ($config->getSubdirectory() === 'config/features_removal') {
      if ($type === FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG) {
        $dependencies[] = strtok($config->getName(), '.');
      }
      else {
        $dependencies[] = $this->entityTypeManager->getDefinition($type)->getProvider();
      }

      if (isset($config->getData()['dependencies']['module'])) {
        $dependencies = array_merge($dependencies, $config->getData()['dependencies']['module']);
      }

      // Only return dependencies for installed modules and not, for example,
      // 'core'.
      $dependencies = array_intersect($dependencies, array_keys($module_list));
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function assignInterPackageDependencies(FeaturesBundleInterface $bundle, array &$packages) {
    if (!$this->packagesPrefixed) {
      throw new \Exception($this->t('The packages have not yet been prefixed with a bundle name.'));
    }

    $config_collection = $this->getConfigCollection();
    $module_list = $this->moduleHandler->getModuleList();

    /** @var \Drupal\features\Package[] $packages */
    foreach ($packages as $package) {
      foreach ($package->getConfig() as $item_name) {
        if (!empty($config_collection[$item_name]->getData()['dependencies']['config'])) {
          foreach ($config_collection[$item_name]->getData()['dependencies']['config'] as $dependency_name) {
            if (isset($config_collection[$dependency_name]) &&
              // For configuration in the
              // InstallStorage::CONFIG_INSTALL_DIRECTORY directory, set any
              // package dependencies of the configuration item.
              // As its name implies, the core-provided
              // InstallStorage::CONFIG_OPTIONAL_DIRECTORY should not create
              // dependencies.
//              ($config_collection[$dependency_name]->getSubdirectory() === InstallStorage::CONFIG_INSTALL_DIRECTORY)) {
              ($config_collection[$dependency_name]->getSubdirectory() === 'config/features_removal')) {
              // If the required item is assigned to one of the packages, add
              // a dependency on that package.
              $dependency_set = FALSE;
              if ($dependency_package = $config_collection[$dependency_name]->getPackage()) {
                $package_name = $bundle->getFullName($dependency_package);
                // Package shouldn't be dependent on itself.
                if ($package_name && array_key_exists($package_name, $packages) && $package_name != $package->getMachineName() && isset($module_list[$package_name])) {
                  $package->setDependencies($this->mergeUniqueItems($package->getDependencies(), [$package_name]));
                  $dependency_set = TRUE;
                }
              }
              // Otherwise, if the dependency is provided by an existing
              // feature, add a dependency on that feature.
              if (!$dependency_set && $extension_name = $config_collection[$dependency_name]->getProvider()) {
                // No extension should depend on the install profile.
                $package_name = $bundle->getFullName($package->getMachineName());
                if ($extension_name != $package_name && $extension_name != $this->drupalGetProfile() && isset($module_list[$extension_name])) {
                  $package->setDependencies($this->mergeUniqueItems($package->getDependencies(), [$extension_name]));
                }
              }
            }
          }
        }
      }
    }
    // Unset the $package pass by reference.
    unset($package);
  }

  /**
   * Loads configuration from storage into a property.
   */
  protected function initConfigCollection($reset = FALSE) {
    if ($reset || empty($this->configCollection)) {
      $config_collection = [];
      $config_types = $this->listConfigTypes();
      $dependency_manager = $this->getFeaturesConfigDependencyManager();
      // List configuration provided by installed features.
      $existing_config = $this->listExistingConfig(NULL);
      $existing_config_by_directory = $this->extensionStorages->listAllByDirectory();
      foreach (array_keys($config_types) as $config_type) {
        $config = $this->listConfigByType($config_type);
        foreach ($config as $item_name => $label) {
          $name = $this->getFullName($config_type, $item_name);
          $data = $this->configStorage->read($name);

          $config_collection[$name] = (new ConfigurationItem($name, $data, [
            'shortName' => $item_name,
            'label' => $label,
            'type' => $config_type,
            'dependents' => array_keys($dependency_manager->getDependentEntities('config', $name)),
            // Default to the install directory.
//            'subdirectory' => isset($existing_config_by_directory[$name]) ? $existing_config_by_directory[$name] : InstallStorage::CONFIG_INSTALL_DIRECTORY,
            'subdirectory' => isset($existing_config_by_directory[$name]) ? $existing_config_by_directory[$name] : 'config/features_removal',
            'package' => '',
            'providerExcluded' => NULL,
            'provider' => isset($existing_config[$name]) ? $existing_config[$name] : NULL,
            'packageExcluded' => [],
          ]));
        }
      }
      $this->setConfigCollection($config_collection);
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesManager.
 */

namespace Drupal\features;
use Drupal;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesBundleInterface;
use Drupal\features\FeaturesGeneratorInterface;
use Drupal\features\FeaturesExtensionStorages;
use Drupal\features\FeaturesExtensionStoragesInterface;
use Drupal\features\FeaturesManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The FeaturesManager provides helper functions for building packages.
 */
class FeaturesManager implements FeaturesManagerInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The extension storages.
   *
   * @var \Drupal\features\FeaturesExtensionStoragesInterface
   */
  protected $extensionStorages;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Features settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The Features assignment settings.
   *
   * @var array
   */
  protected $assignmentSettings;

  /**
   * The configuration present on the site.
   *
   * @var \Drupal\features\ConfigurationItem[]
   */
  private $configCollection;

  /**
   * The packages to be generated.
   *
   * @var array
   */
  protected $packages;

  /**
   * The package assigner.
   *
   * @var \Drupal\features\FeaturesAssigner
   */
  protected $assigner;

  /**
   * Constructs a FeaturesManager object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The target storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory,
                              StorageInterface $config_storage, ConfigManagerInterface $config_manager,
                              ModuleHandlerInterface $module_handler) {
    $this->entityManager = $entity_manager;
    $this->configStorage = $config_storage;
    $this->configManager = $config_manager;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->settings = $config_factory->getEditable('features.settings');
    $this->assignmentSettings = $config_factory->getEditable('features.assignment');
    $this->extensionStorages = new FeaturesExtensionStorages($this->configStorage);
    $this->extensionStorages->addStorage(InstallStorage::CONFIG_INSTALL_DIRECTORY);
    $this->extensionStorages->addStorage(InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
    $this->packages = [];
    $this->configCollection = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveStorage() {
    return $this->configStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionStorages() {
    return $this->extensionStorages;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullName($type, $name) {
    if ($type == FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG || !$type) {
      return $name;
    }

    $definition = $this->entityManager->getDefinition($type);
    $prefix = $definition->getConfigPrefix() . '.';
    return $prefix . $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigType($fullname) {
    $result = array(
      'type' => '',
      'name_short' => '',
    );
    $prefix = FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG . '.';
    if (strpos($fullname, $prefix)) {
      $result['type'] = FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG;
      $result['name_short'] = substr($fullname, strlen($prefix));
    }
    else {
      foreach ($this->entityManager->getDefinitions() as $entity_type => $definition) {
        if ($definition->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
          $prefix = $definition->getConfigPrefix() . '.';
          if (strpos($fullname, $prefix) === 0) {
            $result['type'] = $entity_type;
            $result['name_short'] = substr($fullname, strlen($prefix));
          }
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->packages = [];
    // Don't use getConfigCollection because reset() may be called in
    // cases where we don't need to load config.
    foreach ($this->configCollection as $config) {
      $config->setPackage(NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigCollection($reset = FALSE) {
    $this->initConfigCollection($reset);
    return $this->configCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigCollection(array $config_collection) {
    $this->configCollection = $config_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackages() {
    return $this->packages;
  }

  /**
   * {@inheritdoc}
   */
  public function setPackages(array $packages) {
    $this->packages = $packages;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackage($machine_name) {
    if (isset($this->packages[$machine_name])) {
      return $this->packages[$machine_name];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setPackage(array &$package) {
    if (!empty($package['machine_name'])) {
      $this->packages[$package['machine_name']] = $package;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function filterPackages(array $packages, $namespace = '', $only_exported = FALSE) {
    $result = array();
    foreach ($packages as $key => $package) {
      // A package matches the namespace if:
      // - it's prefixed with the namespace, or
      // - it's assigned to a bundle named for the namespace, or
      // - we're looking only for exported packages and it's not exported.
      if (empty($namespace) || (strpos($package['machine_name'], $namespace . '_') === 0) ||
        (isset($package['bundle']) && $package['bundle'] === $namespace) ||
        ($only_exported && $package['status'] === FeaturesManagerInterface::STATUS_NO_EXPORT)) {
        $result[$key] = $package;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssigner() {
    if (empty($this->assigner)) {
      $this->setAssigner(\Drupal::service('features_assigner'));
    }
    return $this->assigner;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssigner(FeaturesAssignerInterface $assigner) {
    $this->assigner = $assigner;
    $this->reset();
  }

  /**
   * {@inheritdoc}
   */
  public function getGenerator() {
    return $this->generator;
  }

  /**
   * {@inheritdoc}
   */
  public function setGenerator(FeaturesGeneratorInterface $generator) {
    $this->generator = $generator;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportSettings() {
    return $this->settings->get('export');
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionInfo(Extension $extension) {
    return \Drupal::service('info_parser')->parse($extension->getPathname());
  }

  /**
   * {@inheritdoc}
   */
  public function isFeatureModule(Extension $module, FeaturesBundleInterface $bundle = NULL) {
    $info = $this->getExtensionInfo($module);
    if (isset($info['features'])) {
      // If no bundle was requested, it's enough that this is a feature.
      if (is_null($bundle)) {
        return TRUE;
      }
      // If the default bundle was requested, look for features where
      // the bundle is not set.
      elseif ($bundle->isDefault()) {
        return !isset($info['features']['bundle']);
      }
      // If we have a bundle name, look for it.
      else {
        return (isset($info['features']['bundle']) && ($info['features']['bundle'] == $bundle->getMachineName()));
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function listPackageDirectories(array $machine_names = array(), FeaturesBundleInterface $bundle = NULL) {
    if (empty($machine_names)) {
      $machine_names = array_keys($this->getPackages());
    }

    // If the bundle is a profile, then add the profile's machine name.
    if (isset($bundle) && $bundle->isProfile() && !in_array($bundle->getProfileName(), $machine_names)) {
      $machine_names[] = $bundle->getProfileName();
    }

    $modules = $this->getFeaturesModules($bundle);
    // Filter to include only the requested packages.
    $modules = array_filter($modules, function ($module) use ($bundle, $machine_names) {
      $short_name = $bundle->getShortName($module->getName());
      return in_array($short_name, $machine_names);
    });

    $directories = array();
    foreach ($modules as $module) {
      $directories[$module->getName()] = $module->getPath();
    }

    return $directories;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllModules() {
    static $modules;

    if (!isset($modules)) {
      // ModuleHandler::getModuleDirectories() returns data only for installed
      // modules. system_rebuild_module_data() includes only the site's install
      // profile directory, while we may need to include a custom profile.
      // @see _system_rebuild_module_data().
      $listing = new ExtensionDiscovery(\Drupal::root());

      $profile_directories = [];
      // Register the install profile.
      $installed_profile = drupal_get_profile();
      if ($installed_profile) {
        $profile_directories[] = drupal_get_path('profile', $installed_profile);
      }
      if (isset($bundle) && $bundle->isProfile()) {
        $profile_directory = 'profiles/' . $bundle->getProfileName();
        if (($bundle->getProfileName() != $installed_profile) && is_dir($profile_directory)) {
          $profile_directories[] = $profile_directory;
        }
      }
      $listing->setProfileDirectories($profile_directories);

      // Find modules.
      $modules = $listing->scan('module');

      // Find installation profiles.
      $profiles = $listing->scan('profile');

      foreach ($profiles as $key => $profile) {
        $modules[$key] = $profile;
      }
    }

    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeaturesModules(FeaturesBundleInterface $bundle = NULL, $enabled = FALSE) {
    $modules = $this->getAllModules();

    // Filter by bundle.
    if (isset($bundle)) {
      $features_manager = $this;
      $modules = array_filter($modules, function ($module) use ($features_manager, $bundle) {
        return $features_manager->isFeatureModule($module, $bundle);
      });
    }

    // Filtered by enabled status.
    if ($enabled) {
      $features_manager = $this;
      $modules = array_filter($modules, function ($extension) use ($features_manager) {
        return $features_manager->moduleHandler->moduleExists($extension->getName());
      });
    }

    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function initPackage($machine_name, $name = NULL, $description = '', $type = 'module', FeaturesBundleInterface $bundle = NULL, Extension $extension = NULL) {
    if (!isset($this->packages[$machine_name])) {
      return $this->packages[$machine_name] = $this->getPackageArray($machine_name, $name, $description, $type, $bundle, $extension);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function initPackageFromExtension(Extension $extension) {
    $info = $this->getExtensionInfo($extension);
    $bundle = $this->getAssigner()->findBundle($info);
    $short_name = $bundle->getShortName($extension->getName());
    return $this->initPackage($short_name, $info['name'], !empty($info['description']) ? $info['description'] : '', $info['type'], $bundle, $extension);
  }

  /**
   * {@inheritdoc}
   */
  public function assignConfigPackage($package_name, array $item_names, $force = FALSE) {
    $config_collection = $this->getConfigCollection();

    $packages =& $this->packages;
    if (isset($packages[$package_name])) {
      $package =& $packages[$package_name];
    }
    else {
      throw new \Exception($this->t('Failed to package @package_name. Package not found.', ['@package_name' => $package_name]));
    }

    foreach ($item_names as $item_name) {
      if (isset($config_collection[$item_name])) {
        // Add to the package if:
        // - force is set or
        //   - the item hasn't already been assigned elsewhere, and
        //   - the package hasn't been excluded.
        // - and the item isn't already in the package.

        // Determine if the item is provided by an extension.
        $extension_provided = ($config_collection[$item_name]->isExtensionProvided() === TRUE);
        $already_assigned = !empty($config_collection[$item_name]->getPackage());
        // If this is the profile package, we can reassign extension-provided configuration.
        $assignable = (!$extension_provided || $this->getAssigner()->getBundle($package['bundle'])->isProfilePackage($package['machine_name']));
        $excluded_from_package = in_array($package_name, $config_collection[$item_name]->getPackageExcluded());
        $already_in_package = in_array($item_name, $package['config']);
        if (($force || (!$already_assigned && $assignable && !$excluded_from_package)) && !$already_in_package) {
          // Add the item to the package's config array.
          $package['config'][] = $item_name;
          // Mark the item as already assigned.
          $config_collection[$item_name]->setPackage($package_name);
          // For configuration in the InstallStorage::CONFIG_INSTALL_DIRECTORY
          // directory, set any module dependencies of the configuration item
          // as package dependencies.
          // As its name implies, the core-provided
          // InstallStorage::CONFIG_OPTIONAL_DIRECTORY should not create
          // dependencies.
          if ($config_collection[$item_name]->getSubdirectory() === InstallStorage::CONFIG_INSTALL_DIRECTORY && isset($config_collection[$item_name]->getData()['dependencies']['module'])) {
            $dependencies =& $package['dependencies'];
            $this->mergeUniqueItems($dependencies, $config_collection[$item_name]->getData()['dependencies']['module']);
          }
        }
      }
    }

    $this->setConfigCollection($config_collection);
  }

  /**
   * {@inheritdoc}
   */
  public function assignConfigByPattern(array $patterns) {
    // Regular expressions for items that are likely to generate false
    // positives when assigned by pattern.
    $false_positives = [
      // Blocks with the page title should not be assigned to a 'page' package.
      '/block\.block\..*_page_title/',
    ];
    $config_collection = $this->getConfigCollection();
    // Reverse sort by key so that child package will claim items before parent
    // package. E.g., event_registration will claim before event.
    krsort($config_collection);
    foreach ($patterns as $pattern => $machine_name) {
      if (isset($this->packages[$machine_name])) {
        foreach ($config_collection as $item_name => $item) {
          // Test for and skip false positives.
          foreach ($false_positives as $false_positive) {
            if (preg_match($false_positive, $item_name)) {
              continue 2;
            }
          }

          if (!$item->getPackage() && preg_match('/[_\-.]' . $pattern . '[_\-.]/', '.' . $item->getShortName() . '.')) {
            try {
              $this->assignConfigPackage($machine_name, [$item_name]);
            }
            catch (\Exception $exception) {
              \Drupal::logger('features')->error($exception->getMessage());
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assignConfigDependents(array $item_names = NULL, $package = NULL) {
    $config_collection = $this->getConfigCollection();
    if (empty($item_names)) {
      $item_names = array_keys($config_collection);
    }
    foreach ($item_names as $item_name) {
      // Make sure the extension provided item exists in the active
      // configuration storage.
      if (isset($config_collection[$item_name]) && $config_collection[$item_name]->getPackage()) {
        foreach ($config_collection[$item_name]->getDependents() as $dependent_item_name) {
          if (isset($config_collection[$dependent_item_name]) && (!empty($package) || empty($config_collection[$dependent_item_name]->getPackage()))) {
            try {
              $package_name = !empty($package) ? $package : $config_collection[$item_name]->getPackage();
              // If a Package is specified, force assign it to the given
              // package.
              $this->assignConfigPackage($package_name, [$dependent_item_name], !empty($package));
            }
            catch (\Exception $exception) {
              \Drupal::logger('features')->error($exception->getMessage());
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assignInterPackageDependencies(array &$packages) {
    $config_collection = $this->getConfigCollection();
    foreach ($packages as &$package) {
      foreach ($package['config'] as $item_name) {
        if (!empty($config_collection[$item_name]->getData()['dependencies']['config'])) {
          foreach ($config_collection[$item_name]->getData()['dependencies']['config'] as $dependency_name) {
            if (isset($config_collection[$dependency_name])) {
              // If the required item is assigned to one of the packages, add
              // a dependency on that package.
              if ($config_collection[$dependency_name]->getPackage() && array_key_exists($config_collection[$dependency_name]->getPackage(), $packages)) {
                $dependency_package = $packages[$config_collection[$dependency_name]->getPackage()];
                $dependency_bundle = $this->getAssigner()->getBundle($dependency_package['bundle']);
                $this->mergeUniqueItems($package['dependencies'], [$dependency_bundle->getFullName($dependency_package['machine_name'])]);
              }
              // Otherwise, if the dependency is provided by an existing
              // feature, add a dependency on that feature.
              elseif ($config_collection[$dependency_name]->getProvidingFeature()) {
                $this->mergeUniqueItems($package['dependencies'], [$config_collection[$dependency_name]->getProvidingFeature()]);
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
   * Merges a set of new item into an array and sorts the result.
   *
   * Only unique values are retained.
   *
   * @param array &$items
   *   An array of items.
   * @param array $new_items
   *   An array of new items to be merged in.
   */
  protected function mergeUniqueItems(&$items, $new_items) {
    $items = array_unique(array_merge($items, $new_items));
    sort($items);
  }

  /**
   * Initializes and returns a package or profile array.
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
   *
   * @return array
   *   An array of package properties; see
   *   FeaturesManagerInterface::getPackages().
   */
  protected function getPackageArray($machine_name, $name = NULL, $description = '', $type = 'module', FeaturesBundleInterface $bundle = NULL, Extension $extension = NULL) {
    if (!isset($bundle)) {
      $bundle = $this->getAssigner()->getBundle();
    }
    $package = [
      'machine_name' => $machine_name,
      'name' => isset($name) ? $name : ucwords(str_replace(['_', '-'], ' ', $machine_name)),
      'description' => $description,
      'type' => $type,
      'core' => Drupal::CORE_COMPATIBILITY,
      'dependencies' => [],
      'themes' => [],
      'config' => [],
      'status' => FeaturesManagerInterface::STATUS_DEFAULT,
      'version' => '',
      'state' => FeaturesManagerInterface::STATE_DEFAULT,
      'directory' => $machine_name,
      'files' => [],
      'bundle' => $bundle->isDefault() ? '' : $bundle->getMachineName(),
      'extension' => NULL,
      'info' => [],
      'config_orig' => [],
    ];

    // If no extension was passed in, look for a match.
    if (!isset($extension)) {
      $module_list = $this->getFeaturesModules($bundle);
      $full_name = $bundle->getFullName($package['machine_name']);
      if (isset($module_list[$full_name])) {
        $extension = $module_list[$full_name];
      }
    }

    // If there is an extension, set extension-specific properties.
    if (isset($extension)) {
      $info = $this->getExtensionInfo($extension);
      $package['extension'] = $extension;
      $package['info'] = $info;
      $package['config_orig'] = $this->listExtensionConfig($extension);
      $package['status'] = $this->moduleHandler->moduleExists($extension->getName())
        ? FeaturesManagerInterface::STATUS_ENABLED
        : FeaturesManagerInterface::STATUS_DISABLED;
      $package['version'] = isset($info['version']) ? $info['version'] : '';
    }

    return $package;
  }

  /**
   * Generates and adds .info.yml files to a package.
   *
   * @param array $package
   *   The package.
   */
  protected function addInfoFile(array &$package) {
    // Filter to standard keys of the profiles that we will use in info files.
    $info_keys = [
      'name',
      'description',
      'type',
      'core',
      'dependencies',
      'themes',
      'version'
    ];
    $info = array_intersect_key($package, array_fill_keys($info_keys, NULL));

    // Assign to a "package" named for the profile.
    if (isset($package['bundle'])) {
      $bundle = $this->getAssigner()->getBundle($package['bundle']);
    }
    // Save the current bundle in the info file so the package
    // can be reloaded later by the AssignmentPackages plugin.
    if (isset($bundle) && !$bundle->isDefault()) {
      $info['package'] = $bundle->getName();
      $info['features']['bundle'] = $bundle->getMachineName();
    }
    else {
      unset($info['features']['bundle']);
    }

    if (!empty($package['config'])) {
      foreach (array('excluded', 'required') as $constraint) {
        if (!empty($package[$constraint])) {
          $info['features'][$constraint] = $package[$constraint];
        }
        else {
          unset($info['features'][$constraint]);
        }
      }

      if (empty($info['features'])) {
        $info['features'] = TRUE;
      }
    }

    // The name and description need to be cast as strings from the
    // TranslatableMarkup objects returned by t() to avoid raising an
    // InvalidDataTypeException on Yaml serialization.
    foreach (array('name', 'description') as $key) {
      $info[$key] = (string) $info[$key];
    }

    // Add profile-specific info data.
    if ($info['type'] == 'profile') {
      // Set the distribution name.
      $info['distribution'] = [
        'name' => $info['name']
      ];
    }

    $package['files']['info'] = [
      'filename' => $package['machine_name'] . '.info.yml',
      'subdirectory' => NULL,
      // Filter to remove any empty keys, e.g., an empty themes array.
      'string' => Yaml::encode(array_filter($info))
    ];
  }

  /**
   * Generates and adds files to a given package or profile.
   */
  protected function addPackageFiles(array &$package) {
    $config_collection = $this->getConfigCollection();
    // Ensure the directory reflects the current full machine name.
    $package['directory'] = $package['machine_name'];
    // Only add files if there is at least one piece of configuration
    // present.
    if (!empty($package['config'])) {
      // Add .info.yml files.
      $this->addInfoFile($package);

      // Add configuration files.
      foreach ($package['config'] as $name) {
        $config = $config_collection[$name];
        // The UUID is site-specfic, so don't export it.
        if ($entity_type_id = $this->configManager->getEntityTypeIdByName($name)) {
          $data = $config->getData();
          unset($data['uuid']);
          $config->setData($data);
        }
        // User roles include all permissions currently assigned to them. To
        // avoid extraneous additions, reset permissions.
        if ($config->getType() == 'user_role') {
          $data = $config->getData();
          $data['permissions'] = [];
          $config->setData($data);
        }
        $package['files'][$name] = [
          'filename' => $config->getName() . '.yml',
          'subdirectory' => $config->getSubdirectory(),
          'string' => Yaml::encode($config->getData())
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mergeInfoArray(array $info1, array $info2, array $keys = array()) {
    // If keys were specified, use only those.
    if (!empty($keys)) {
      $info2 = array_intersect_key($info2, array_fill_keys($keys, NULL));
    }

    // Ensure the entire 'features' data is replaced by new data.
    if (isset($info2['features'])) {
      unset($info1['features']);
    }

    $info = NestedArray::mergeDeep($info1, $info2);

    // Process the dependencies and themes keys.
    $keys = ['dependencies', 'themes'];
    foreach ($keys as $key) {
      if (isset($info[$key]) && is_array($info[$key])) {
        // NestedArray::mergeDeep() may produce duplicate values.
        $info[$key] = array_unique($info[$key]);
        sort($info[$key]);
      }
    }
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function listConfigTypes($bundles_only = FALSE) {
    $definitions = [];
    foreach ($this->entityManager->getDefinitions() as $entity_type => $definition) {
      if ($definition->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
        if (!$bundles_only || $definition->getBundleOf()) {
          $definitions[$entity_type] = $definition;
        }
      }
    }
    $entity_types = array_map(function (EntityTypeInterface $definition) {
      return $definition->getLabel();
    }, $definitions);
    // Sort the entity types by label, then add the simple config to the top.
    uasort($entity_types, 'strnatcasecmp');
    return $bundles_only ? $entity_types : [
      FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG => $this->t('Simple configuration'),
    ] + $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function listExtensionConfig(Extension $extension) {
    return $this->extensionStorages->listExtensionConfig($extension);
  }

  /**
   * {@inheritdoc}
   */
  public function listExistingConfig($enabled = FALSE, FeaturesBundleInterface $bundle = NULL) {
    $config = array();
    $existing = $this->getFeaturesModules($bundle, $enabled);
    foreach ($existing as $extension) {
      // Keys are configuration item names and values are providing extension
      // name.
      $new_config = array_fill_keys($this->listExtensionConfig($extension), $extension->getName());
      $config = array_merge($config, $new_config);
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function listConfigByType($config_type) {
    // For a given entity type, load all entities.
    if ($config_type && $config_type !== FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG) {
      $entity_storage = $this->entityManager->getStorage($config_type);
      $names = [];
      foreach ($entity_storage->loadMultiple() as $entity) {
        $entity_id = $entity->id();
        $label = $entity->label() ?: $entity_id;
        $names[$entity_id] = $label;
      }
    }
    // Handle simple configuration.
    else {
      $definitions = [];
      foreach ($this->entityManager->getDefinitions() as $entity_type => $definition) {
        if ($definition->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
          $definitions[$entity_type] = $definition;
        }
      }
      // Gather the config entity prefixes.
      $config_prefixes = array_map(function (EntityTypeInterface $definition) {
        return $definition->getConfigPrefix() . '.';
      }, $definitions);

      // Find all config, and then filter our anything matching a config prefix.
      $names = $this->configStorage->listAll();
      $names = array_combine($names, $names);
      foreach ($names as $item_name) {
        foreach ($config_prefixes as $config_prefix) {
          if (strpos($item_name, $config_prefix) === 0) {
            unset($names[$item_name]);
          }
        }
      }
    }
    return $names;
  }

  /**
   * Loads configuration from storage into a property.
   */
  protected function initConfigCollection($reset = FALSE) {
    if ($reset || empty($this->configCollection)) {
      $config_collection = [];
      $config_types = $this->listConfigTypes();
      $dependency_manager = $this->configManager->getConfigDependencyManager();
      // List configuration provided by installed features.
      $existing_config = $this->listExistingConfig(TRUE);
      foreach (array_keys($config_types) as $config_type) {
        $config = $this->listConfigByType($config_type);
        foreach ($config as $item_name => $label) {
          $name = $this->getFullName($config_type, $item_name);
          $data = $this->configStorage->read($name);

          // Compute dependent config.
          $dependent_list = $dependency_manager->getDependentEntities('config', $name);
          $dependents = array();
          foreach ($dependent_list as $config_name => $item) {
            if (!isset($dependents[$config_name])) {
              $dependents[$config_name] = $config_name;
            }
            // Grab any dependent graph paths.
            if (isset($item['reverse_paths'])) {
              foreach ($item['reverse_paths'] as $dependent_name => $value) {
                if ($value && !isset($dependents[$dependent_name])) {
                  $dependents[$dependent_name] = $dependent_name;
                }
              }
            }
          }

          $config_collection[$name] = (new ConfigurationItem($name, $data, [
            'shortName' => $item_name,
            'label' => $label,
            'type' => $config_type,
            'dependents' => array_keys($dependents),
            // Default to the install directory.
            'subdirectory' => InstallStorage::CONFIG_INSTALL_DIRECTORY,
            'package' => '',
            'extensionProvided' => NULL,
            'providingFeature' => isset($existing_config[$name]) ? $existing_config[$name] : NULL,
            'packageExcluded' => [],
          ]));
        }
      }
      $this->setConfigCollection($config_collection);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFiles(array &$packages) {
    foreach ($packages as &$package) {
      $this->addPackageFiles($package);
    }
    // Clean up the $package pass by reference.
    unset($package);
  }

  /**
   * {@inheritdoc}
   */
  public function getExportInfo($package, FeaturesBundleInterface $bundle = NULL) {
    $full_name = isset($bundle) ? $bundle->getFullName($package['machine_name']) : $package['machine_name'];

    $path = '';

    // Adjust export directory to be in profile.
    if (isset($bundle) && $bundle->isProfile()) {
      $path .= 'profiles/' . $bundle->getProfileName();
    }

    // If this is not the profile package, nest the directory.
    if (!isset($bundle) || !$bundle->isProfilePackage($package['machine_name'])) {
      $path .= empty($path) ? 'modules' : '/modules';
      $export_settings = $this->getExportSettings();
      if (!empty($export_settings['folder'])) {
        $path .= '/' . $export_settings['folder'];
      }
    }

    return array($full_name, $path);
  }

  /**
   * {@inheritdoc}
   */
  public function detectOverrides(array $feature, $include_new = FALSE) {
    /** @var \Drupal\config_update\ConfigDiffInterface $config_diff */
    $config_diff = \Drupal::service('config_update.config_diff');

    $different = array();
    foreach ($feature['config'] as $name) {
      $active = $this->configStorage->read($name);
      $extension = $this->extensionStorages->read($name);
      $extension = !empty($extension) ? $extension : array();
      if (($include_new || !empty($extension)) && !$config_diff->same($extension, $active)) {
        $different[] = $name;
      }
    }

    if (!empty($different)) {
      $feature['state'] = FeaturesManagerInterface::STATE_OVERRIDDEN;
    }
    return $different;
  }

  /**
   * {@inheritdoc}
   */
  public function detectNew(array $feature) {
    $result = array();
    foreach ($feature['config'] as $name) {
      $extension = $this->extensionStorages->read($name);
      if (empty($extension)) {
        $result[] = $name;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function detectMissing(array $feature) {
    $config = $this->getConfigCollection();
    $result = array();
    foreach ($feature['config_orig'] as $name) {
      if (!isset($config[$name])) {
        $result[] = $name;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function reorderMissing(array $missing) {
    $list = array();
    $result = array();
    foreach ($missing as $full_name) {
      $this->addConfigList($full_name, $list);
    }
    foreach ($list as $full_name) {
      if (in_array($full_name, $missing)) {
        $result[] = $full_name;
      }
    }
    return $result;
  }

  protected function addConfigList($full_name, &$list) {
    if (!in_array($full_name, $list)) {
      array_unshift($list, $full_name);
      $value = $this->extensionStorages->read($full_name);
      if (isset($value['dependencies']['config'])) {
        foreach ($value['dependencies']['config'] as $config_name) {
          $this->addConfigList($config_name, $list);
        }
      }
    }
  }

    /**
   * {@inheritdoc}
   */
  public function statusLabel($status) {
    switch ($status) {
      case FeaturesManagerInterface::STATUS_NO_EXPORT:
        return t('Not exported');

      case FeaturesManagerInterface::STATUS_DISABLED:
        return t('Uninstalled');

      case FeaturesManagerInterface::STATUS_ENABLED:
        return t('Enabled');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function stateLabel($state) {
    switch ($state) {
      case FeaturesManagerInterface::STATE_DEFAULT:
        return t('Default');

      case FeaturesManagerInterface::STATE_OVERRIDDEN:
        return t('Changed');
    }
  }

}

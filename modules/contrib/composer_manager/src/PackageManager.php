<?php

/**
 * @file
 * Contains \Drupal\composer_manager\PackageManager.
 */

namespace Drupal\composer_manager;

/**
 * Manages composer packages.
 */
class PackageManager implements PackageManagerInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * A cache of loaded packages.
   *
   * @var array
   */
  protected $packages = [];

  /**
   * Constructs a PackageManager object.
   *
   * @param string $root
   *   The drupal root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function getCorePackage() {
    if (!isset($this->packages['core'])) {
      $this->packages['core'] = JsonFile::read($this->root . '/core/composer.json');
    }

    return $this->packages['core'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionPackages() {
    if (!isset($this->packages['extension'])) {
      $listing = new ExtensionDiscovery($this->root);
      // Get all profiles, and modules belonging to those profiles.
      // @todo Scan themes as well?
      $profiles = $listing->scan('profile');
      $profile_directories = array_map(function ($profile) {
        return $profile->getPath();
      }, $profiles);
      $listing->setProfileDirectories($profile_directories);
      $modules = $listing->scan('module');
      $extensions = $profiles + $modules;

      $installed_packages = $this->getInstalledPackages();
      $installed_packages = array_map(function ($package) {
        return $package['name'];
      }, $installed_packages);

      $this->packages['extension'] = [];
      foreach ($extensions as $extension_name => $extension) {
        $filename = $this->root . '/' . $extension->getPath() . '/composer.json';
        if (is_readable($filename)) {
          $extension_package = JsonFile::read($filename);
          // The package must at least have a name and some requirements.
          if (empty($extension_package['name'])) {
            continue;
          }
          if (empty($extension_package['require']) && empty($extension_package['require-dev'])) {
            continue;
          }
          if (in_array($extension_package['name'], $installed_packages)) {
            // This extension is already managed with Composer.
            continue;
          }
          // The path is required by rebuildRootPackage().
          $extension_package['extra']['path'] = $extension->getPath() . '/composer.json';

          $this->packages['extension'][$extension_name] = $extension_package;
        }
      }
    }

    return $this->packages['extension'];
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalledPackages() {
    if (!isset($this->packages['installed'])) {
      $this->packages['installed'] = JsonFile::read($this->root . '/vendor/composer/installed.json');
    }

    return $this->packages['installed'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredPackages() {
    if (!isset($this->packages['required'])) {
      $merged_extension_package = $this->buildMergedExtensionPackage();
      $packages = [];
      foreach ($merged_extension_package['require'] as $package_name => $constraint) {
        if (substr($package_name, 0, 7) != 'drupal/') {
          // Skip Drupal module requirements, add the rest.
          $packages[$package_name] = [
            'constraint' => $constraint,
          ];
        }
      }

      foreach ($this->getInstalledPackages() as $package) {
        $package_name = $package['name'];
        if (!isset($packages[$package_name])) {
          continue;
        }

        // Add additional information available only for installed packages.
        $packages[$package_name] += [
          'description' => !empty($package['description']) ? $package['description'] : '',
          'homepage' => !empty($package['homepage']) ? $package['homepage'] : '',
          'require' => !empty($package['require']) ? $package['require'] : [],
          'version' => $package['version'],
        ];
        if ($package['version'] == 'dev-master') {
          $packages[$package_name]['version'] .= '#' . $package['source']['reference'];
        }
      }

      // Process and cache the package list.
      $this->packages['required'] = $this->processRequiredPackages($packages);
    }

    return $this->packages['required'];
  }

  /**
   * Formats and sorts the provided list of packages.
   *
   * @param array $packages
   *   The packages to process.
   *
   * @return array
   *   The processed packages.
   */
  protected function processRequiredPackages(array $packages) {
    foreach ($packages as $package_name => $package) {
      // Ensure the presence of all keys.
      $packages[$package_name] += [
        'constraint' => '',
        'description' => '',
        'homepage' => '',
        'require' => [],
        'required_by' => [],
        'version' => '',
      ];
      // Sort the keys to ensure consistent results.
      ksort($packages[$package_name]);
    }

    // Sort the packages by package name.
    ksort($packages);

    // Add information about dependent packages.
    $extension_packages = $this->getExtensionPackages();
    foreach ($packages as $package_name => $package) {
      foreach ($extension_packages as $extension_name => $extension_package) {
        if (isset($extension_package['require'][$package_name])) {
          $packages[$package_name]['required_by'] = [$extension_package['name']];
          break;
        }
      }
    }

    return $packages;
  }

  /**
   * {@inheritdoc}
   */
  public function needsComposerUpdate() {
    $needs_update = FALSE;
    foreach ($this->getRequiredPackages() as $package) {
      if (empty($package['version']) || empty($package['required_by'])) {
        $needs_update = TRUE;
        break;
      }
    }

    return $needs_update;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildRootPackage() {
    $root_package = JsonFile::read($this->root . '/composer.json');
    // Initialize known start values. These should match what's already in
    // the root composer.json shipped with Drupal.
    $root_package['replace'] = [
      'drupal/core' => '~8.0',
    ];
    $root_package['repositories'] = [];
    $root_package['extra']['merge-plugin']['include'] = [
      'core/composer.json',
    ];
    // Add the discovered extensions to the replace list so that they doesn't
    // get redownloaded if required by another package.
    foreach ($this->getExtensionPackages() as $extension_package) {
      $version = '8.*';
      if (isset($extension_package['extra']['branch-alias']['dev-master'])) {
        $version = $extension_package['extra']['branch-alias']['dev-master'];
      }
      $root_package['replace'][$extension_package['name']] = $version;
    }
    // Ensure the presence of the Drupal Packagist repository.
    // @todo Remove once Drupal Packagist moves to d.o and gets added to
    // the root package by default.
    $root_package['repositories'][] = [
      'type' => 'composer',
      'url' => 'https://packagist.drupal-composer.org',
    ];
    // Add each discovered extension to the merge list.
    foreach ($this->getExtensionPackages() as $extension_package) {
      $root_package['extra']['merge-plugin']['include'][] = $extension_package['extra']['path'];
    }

    JsonFile::write($this->root . '/composer.json', $root_package);
  }

  /**
   * Builds a package containing the merged fields of all extension packages.
   *
   * Used for reporting purposes only (getRequiredPackages()).
   *
   * @return array
   *   An array with the following keys:
   *   - 'require': The merged requirements
   *   - 'require-dev': The merged dev requirements.
   */
  protected function buildMergedExtensionPackage() {
    $package = [
      'require' => [],
      'require-dev' => [],
    ];
    $keys = array_keys($package);
    foreach ($this->getExtensionPackages() as $extension_package) {
      foreach ($keys as $key) {
        if (isset($extension_package[$key])) {
          $package[$key] = array_merge($extension_package[$key], $package[$key]);
        }
      }
    }
    $package['require'] = $this->filterPlatformPackages($package['require']);
    $package['require-dev'] = $this->filterPlatformPackages($package['require-dev']);

    return $package;
  }

  /**
   * Removes platform packages from the requirements.
   *
   * Platform packages include 'php' and its various extensions ('ext-curl',
   * 'ext-intl', etc). Drupal modules have their own methods for raising the PHP
   * requirement ('php' key in $extension.info.yml) or requiring additional
   * PHP extensions (hook_requirements()).
   *
   * @param array $requirements
   *   The requirements.
   *
   * @return array
   *   The filtered requirements array.
   */
  protected function filterPlatformPackages($requirements) {
    foreach ($requirements as $package => $constraint) {
      if (strpos($package, '/') === FALSE) {
        unset($requirements[$package]);
      }
    }

    return $requirements;
  }

}

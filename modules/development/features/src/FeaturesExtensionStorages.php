<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesExtensionStorages.
 */

namespace Drupal\features;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\Extension;

/**
 * Wraps FeaturesInstallStorage to support multiple configuration
 * directories.
 */
class FeaturesExtensionStorages implements FeaturesExtensionStoragesInterface {

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The extension storages.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $extensionStorages;

  /**
   * Configuration provided by extension storages.
   *
   * @var array
   */
  protected $configurationLists;

  /**
   * Constructs a new FeaturesExtensionStorages object.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The configuration storage.
   */
  public function __construct(StorageInterface $config_storage) {
    $this->configStorage = $config_storage;
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
  public function addStorage($directory = InstallStorage::CONFIG_INSTALL_DIRECTORY) {
    $this->extensionStorages[$directory] = new FeaturesInstallStorage($this->configStorage, $directory);
    $this->reset();
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $list = $this->listAllByDirectory('');
    if (isset($list[$name])) {
      $directory = $list[$name];
      return $this->extensionStorages[$directory]->read($name);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    return array_keys($this->listAllByDirectory($prefix));
  }

  /**
   * {@inheritdoc}
   */
  public function listExtensionConfig(Extension $extension) {
    $extension_config = [];
    foreach ($this->extensionStorages as $directory => $extension_storage) {
      $extension_config = array_merge($extension_config, array_keys($extension_storage->getComponentNames([
        $extension->getName() => $extension,
      ])));
    }
    return $extension_config;
  }

  /**
   * Resets packages and configuration assignment.
   */
  protected function reset() {
    $this->configurationLists = [];
  }

  /**
   * Returns a list of all configuration available from extensions.
   *
   * @param string $prefix
   *   (optional) The prefix to search for. If omitted, all configuration object
   *   names that exist are returned.
   *
   * @return array
   *   An array with configuration item names as keys and configuration
   *   directories as values.
   */
  protected function listAllByDirectory($prefix = '') {
    if (!isset($this->configurationLists[$prefix])) {
      $this->configurationLists[$prefix] = [];
      foreach ($this->extensionStorages as $directory => $extension_storage) {
        $this->configurationLists[$prefix] += array_fill_keys($extension_storage->listAll($prefix), $directory);
      }
    }
    return $this->configurationLists[$prefix];
  }

}

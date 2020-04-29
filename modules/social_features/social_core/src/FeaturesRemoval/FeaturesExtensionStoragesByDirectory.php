<?php
// phpcs:ignoreFile

namespace Drupal\social_core\FeaturesRemoval;

use Drupal\Core\Config\InstallStorage;
use Drupal\features\FeaturesExtensionStoragesByDirectory as FeaturesExtensionStoragesByDirectoryBase;
use Drupal\features\FeaturesInstallStorage;

/**
 * Change `config/install` to `config/features_removal`.
 */
class FeaturesExtensionStoragesByDirectory extends FeaturesExtensionStoragesByDirectoryBase {

  /**
   * {@inheritdoc}
   */
  public function addStorage($directory = InstallStorage::CONFIG_INSTALL_DIRECTORY) {
    if ($directory === InstallStorage::CONFIG_INSTALL_DIRECTORY) {
      $directory = 'config/features_removal';
    }
    $this->extensionStorages[$directory] = new FeaturesInstallStorage($this->configStorage, $directory);
    $this->reset();
  }

}

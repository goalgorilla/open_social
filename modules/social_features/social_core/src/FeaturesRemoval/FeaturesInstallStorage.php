<?php
// phpcs:ignoreFile

namespace Drupal\social_core\FeaturesRemoval;

use Drupal\Core\Config\StorageInterface;
use Drupal\features\FeaturesInstallStorage as FeaturesInstallStorageBase;

/**
 * Change `config/install` to `config/features_removal`.
 */
class FeaturesInstallStorage extends FeaturesInstallStorageBase {
  public function __construct(StorageInterface $config_storage, $directory = self::CONFIG_INSTALL_DIRECTORY, $collection = StorageInterface::DEFAULT_COLLECTION) {
    if ($directory === self::CONFIG_INSTALL_DIRECTORY) {
      $directory = 'config/features_removal';
    }
    parent::__construct($config_storage, $directory, $collection);
  }
}

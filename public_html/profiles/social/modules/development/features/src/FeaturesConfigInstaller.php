<?php

/**
 * @file
 * Contains Drupal\features\FeaturesConfigInstaller.
 */

namespace Drupal\features;

use Drupal\Core\Config\ConfigInstaller;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for customizing the test for pre existing configuration.
 *
 * Copy of ConfigInstaller with findPreExistingConfiguration() modified to
 * allow Feature modules to be installed.
 */
class FeaturesConfigInstaller extends ConfigInstaller {

  /**
   * {@inheritdoc}
   */
  protected function findPreExistingConfiguration(StorageInterface $storage) {
    // CHANGE START
    // Override
    // Drupal\Core\Config\ConfigInstaller::findPreExistingConfiguration().
    // Allow config that already exists coming from Features.
    /** @var \Drupal\features\FeaturesManagerInterface $manager */
    $manager = \Drupal::service('features.manager');
    $features_config = array_keys($manager->listExistingConfig());
    // Map array so we can use isset instead of in_array for faster access.
    $features_config = array_combine($features_config, $features_config);
    // CHANGE END.
    $existing_configuration = array();
    // Gather information about all the supported collections.
    $collection_info = $this->configManager->getConfigCollectionInfo();

    foreach ($collection_info->getCollectionNames() as $collection) {
      $config_to_create = array_keys($this->getConfigToCreate($storage, $collection));
      $active_storage = $this->getActiveStorages($collection);
      foreach ($config_to_create as $config_name) {
        if ($active_storage->exists($config_name)) {
          // CHANGE START
          // Test if config is part of a Feature package.
          if (!isset($features_config[$config_name])) {
            // CHANGE END.
            $existing_configuration[$collection][] = $config_name;
          }
        }
      }
    }
    return $existing_configuration;
  }

}

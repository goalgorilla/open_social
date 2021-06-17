<?php

namespace Drupal\social_core\Service;

use Symfony\Component\Yaml\Yaml;

/**
 * A service class for configurations.
 *
 * @package Drupal\social_core\Service
 */
class ConfigurationsService {

  /**
   * Helper function to import configurations.
   *
   * @param array $configs
   *   Array of configuration file names and their folders.
   */
  public static function importConfigurations(array $configs) {
    foreach ($configs as $folder => $config_files) {
      foreach ($config_files as $config_file) {
        $config = drupal_get_path('module', 'social_group') . "/config/{$folder}/{$config_file}.yml";

        if (is_file($config)) {
          $settings = Yaml::parse(file_get_contents($config));
          if (is_array($settings)) {
            $update_config = \Drupal::configFactory()
              ->getEditable($config_file);

            $update_config->setData($settings)->save(TRUE);
          }
        }
      }
    }
  }

}

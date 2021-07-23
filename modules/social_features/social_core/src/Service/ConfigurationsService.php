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
   * @param string $module_name
   *   The module name for which we import configurations.
   */
  public static function importConfigurations(array $configs, string $module_name) {
    foreach ($configs as $folder => $config_files) {
      foreach ($config_files as $config_file) {
        $config = drupal_get_path('module', $module_name) . "/config/{$folder}/{$config_file}.yml";

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

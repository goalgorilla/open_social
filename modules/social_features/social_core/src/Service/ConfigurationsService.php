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
   * Helper function to import static configurations.
   *
   * @param array $configs
   *   Array of configuration file names and their folders.
   * @param string $func_update_name
   *   The hook_update_N function full name.
   */
  public static function importStaticConfigurations(array $configs, string $func_update_name) {
    // Retrieve module_name and update number.
    $func_update_name = explode('_update_', $func_update_name);

    // We must have at least two items in retrieved data.
    if (is_array($func_update_name) && count($func_update_name) === 2) {
      // Module name have to be first item in retrieved data.
      $module_name = $func_update_name[0];
      // Update number have to be second item in retrieved data.
      $n = $func_update_name[1];

      // Set configurations.
      foreach ($configs as $config) {
        $config_file = drupal_get_path('module', $module_name) . "/config/static/{$config}_$n.yml";

        if (is_file($config_file)) {
          $settings = Yaml::parse(file_get_contents($config_file));
          if (is_array($settings)) {
            $update_config = \Drupal::configFactory()
              ->getEditable($config);

            $update_config->setData($settings)->save(TRUE);
          }
        }
      }
    }
  }

}

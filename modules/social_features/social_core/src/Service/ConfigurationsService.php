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
   * @param string $module_name
   *   The module name for which we import configurations.
   * @param int $n
   *   The hook update number.
   */
  public static function importStaticConfigurations(array $configs, string $module_name, int $n) {
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

<?php

namespace Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPlugin;

use Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPluginBase;

/**
 * Provides a 'SystemData' share usage data plugin.
 *
 * @ShareUsageDataPlugin(
 *  id = "system_data",
 *  label = @Translation("System data"),
 *  setting = "system_data",
 *  weight = -430,
 * )
 */
class SystemData extends ShareUsageDataPluginBase {

  /**
   * Get the value.
   *
   * @return array
   *   $json array.
   */
  public function getValue() {
    $info = [
      'php' => [
        'version' => [
          'version' => PHP_VERSION,
          'major_minor' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
        ],
        'info' => [
          'extensions' => get_loaded_extensions(),
        ],
      ],
      'os' => [
        php_uname(),
      ],
    ];
    return $info;
  }

}

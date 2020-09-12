<?php

namespace Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPlugin;

use Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPluginBase;

/**
 * Provides a 'OpenSocialVersion' share usage data plugin.
 *
 * @ShareUsageDataPlugin(
 *  id = "open_social_version",
 *  label = @Translation("Open Social version"),
 *  setting = "open_social_version",
 *  weight = -450,
 * )
 */
class OpenSocialVersion extends ShareUsageDataPluginBase {

  /**
   * Get the value.
   *
   * @return array
   *   $json array.
   */
  public function getValue() {
    $version = 0;
    $profile = \Drupal::installProfile();
    if ($profile === 'social') {
      $info = \Drupal::service('extension.list.profile')->getExtensionInfo($profile);
      if (!empty($info['version'])) {
        $version = $info['version'];
      }
    }
    return [
      'version' => $version,
    ];
  }

}

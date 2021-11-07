<?php

namespace Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPlugin;

use Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPluginBase;

/**
 * Provides a 'WebsiteUrl' share usage data plugin.
 *
 * @ShareUsageDataPlugin(
 *  id = "website_url",
 *  label = @Translation("Website URL"),
 *  setting = "website_url",
 *  weight = -460,
 * )
 */
class WebsiteUrl extends ShareUsageDataPluginBase {

  /**
   * Get the value.
   *
   *   $json array.
   */
  public function getValue(): array {
    global $base_url;
    return [
      'url' => $base_url,
    ];
  }

}

<?php

namespace Drupal\social_lets_connect_usage\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Share usage data plugin plugins.
 */
interface ShareUsageDataPluginInterface extends PluginInspectionInterface {

  /**
   * Get the value.
   *
   * @return array
   *   $json array.
   */
  public function getValue();

  /**
   * Check if this plugin should be enabled.
   */
  public function enabled();

}

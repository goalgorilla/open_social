<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContextInterface.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Activity content plugins.
 */
interface ActivityContextInterface extends PluginInspectionInterface {

  /**
   * Returns the translated label.
   *
   * @return string
   *   The translated label.
   */
  public function getLabel();

}

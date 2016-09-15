<?php

/**
 * @file
 * Contains \Drupal\activity_send\Plugin\ActivitySendInterface.
 */

namespace Drupal\activity_send\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Activity action plugins.
 */
interface ActivitySendInterface extends PluginInspectionInterface {

  /**
   * Create a new item in queue on the action with some logic behind it.
   */
  public function create($entity);


}

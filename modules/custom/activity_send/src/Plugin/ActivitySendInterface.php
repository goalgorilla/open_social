<?php

namespace Drupal\activity_send\Plugin;

use Drupal\activity_creator\ActivityInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Activity action plugins.
 */
interface ActivitySendInterface extends PluginInspectionInterface {

  /**
   * Create a new item in queue on the action with some logic behind it.
   *
   * @param \Drupal\activity_creator\ActivityInterface $entity
   *   The activity entity object.
   */
  public function process(ActivityInterface $entity) : void;

}

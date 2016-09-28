<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityEntityConditionInterface.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Activity entity condition plugins.
 */
interface ActivityEntityConditionInterface extends PluginInspectionInterface {

  /**
   * Checks if this is a valid entity condition for the action.
   */
  public function isValidEntityCondition($entity);

}

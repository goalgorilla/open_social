<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityDestinationInterface.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Activity destination plugins.
 */
interface ActivityDestinationInterface extends PluginInspectionInterface {

  /**
   * Returns a view mode for the entity.
   */
  public function getViewMode($original_view_mode, $entity);

  /**
   * True or false if the activity destination is set.
   */
  public function isActiveInView($view);

}

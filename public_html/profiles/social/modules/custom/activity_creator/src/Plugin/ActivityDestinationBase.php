<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityDestinationBase.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Activity destination plugins.
 */
abstract class ActivityDestinationBase extends PluginBase implements ActivityDestinationInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewMode($original_view_mode, $entity) {
    return $original_view_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function isActiveInView($view) {
    if (isset($view->filter['field_activity_destinations_value']->value[$this->pluginId])) {
      if ($view->filter['field_activity_destinations_value']->value[$this->pluginId] === $this->pluginId) {
        return TRUE;
      }
    }
    return FALSE;
  }

}

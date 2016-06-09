<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContextBase.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Activity context plugin plugins.
 */
abstract class ActivityContextBase extends PluginBase implements ActivityContextInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t($this->pluginDefinition['label'], array(), array('context' => 'activity_context'));
  }
}

<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Activity entity condition plugins.
 */
abstract class ActivityEntityConditionBase extends PluginBase implements ActivityEntityConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) {
    return TRUE;
  }

}

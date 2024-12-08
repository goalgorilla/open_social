<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for Activity entity condition plugins.
 */
interface ActivityEntityConditionInterface extends PluginInspectionInterface {

  /**
   * Checks if this is a valid entity condition for the action.
   */
  public function isValidEntityCondition(ContentEntityInterface $entity): bool;

}

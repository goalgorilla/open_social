<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ViewExecutable;

/**
 * Defines an interface for Activity destination plugins.
 */
interface ActivityDestinationInterface extends PluginInspectionInterface {

  /**
   * Returns a view mode for the entity.
   */
  public function getViewMode(mixed $original_view_mode, EntityInterface $entity): mixed;

  /**
   * True or false if the activity destination is set.
   */
  public function isActiveInView(ViewExecutable $view): bool;

}

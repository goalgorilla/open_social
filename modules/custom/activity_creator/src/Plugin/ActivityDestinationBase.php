<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ViewExecutable;

/**
 * Base class for Activity destination plugins.
 */
abstract class ActivityDestinationBase extends PluginBase implements ActivityDestinationInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewMode(mixed $original_view_mode, EntityInterface $entity): mixed {
    return $original_view_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function isActiveInView(ViewExecutable $view): bool {
    if (isset($view->filter['field_activity_destinations_value']->value[$this->pluginId])) {
      if ($view->filter['field_activity_destinations_value']->value[$this->pluginId] === $this->pluginId) {
        return TRUE;
      }
    }
    return FALSE;
  }

}

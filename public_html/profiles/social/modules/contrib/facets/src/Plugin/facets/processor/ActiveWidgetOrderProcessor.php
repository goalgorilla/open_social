<?php

namespace Drupal\facets\Plugin\facets\processor;


use Drupal\facets\Processor\WidgetOrderPluginBase;
use Drupal\facets\Processor\WidgetOrderProcessorInterface;
use Drupal\facets\Result\Result;

/**
 * A processor that orders the results by active state.
 *
 * @FacetsProcessor(
 *   id = "active_widget_order",
 *   label = @Translation("Sort by active state"),
 *   description = @Translation("Sorts the widget results by active state."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class ActiveWidgetOrderProcessor extends WidgetOrderPluginBase implements WidgetOrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function sortResults(array $results, $order = 'ASC') {
    if ($order === 'ASC') {
      usort($results, 'self::sortActiveAsc');
    }
    else {
      usort($results, 'self::sortActiveDesc');
    }

    return $results;
  }

  /**
   * Sorts ascending.
   */
  protected static function sortActiveAsc(Result $a, Result $b) {
    if ($a->isActive() == $b->isActive()) {
      return 0;
    }
    return ($a->isActive()) ? -1 : 1;
  }

  /**
   * Sorts descending.
   */
  protected static function sortActiveDesc(Result $a, Result $b) {
    if ($a->isActive() == $b->isActive()) {
      return 0;
    }
    return ($a->isActive()) ? 1 : -1;
  }

}

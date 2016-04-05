<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\Processor\WidgetOrderPluginBase;
use Drupal\facets\Processor\WidgetOrderProcessorInterface;
use Drupal\facets\Result\Result;

/**
 * A processor that orders the results by display value.
 *
 * @FacetsProcessor(
 *   id = "display_value_widget_order",
 *   label = @Translation("Sort by display value"),
 *   description = @Translation("Sorts the widget results by display value."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class DisplayValueWidgetOrderProcessor extends WidgetOrderPluginBase implements WidgetOrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function sortResults(array $results, $order = 'ASC') {
    if ($order === 'ASC') {
      usort($results, 'self::sortDisplayValueAsc');
    }
    else {
      usort($results, 'self::sortDisplayValueDesc');
    }

    return $results;
  }

  /**
   * Sorts ascending.
   */
  protected static function sortDisplayValueAsc(Result $a, Result $b) {
    return strnatcasecmp($a->getDisplayValue(), $b->getDisplayValue());
  }

  /**
   * Sorts descending.
   */
  protected static function sortDisplayValueDesc(Result $a, Result $b) {
    return strnatcasecmp($b->getDisplayValue(), $a->getDisplayValue());
  }

}

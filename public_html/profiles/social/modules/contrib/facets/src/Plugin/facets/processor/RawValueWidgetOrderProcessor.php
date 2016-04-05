<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\Processor\WidgetOrderPluginBase;
use Drupal\facets\Processor\WidgetOrderProcessorInterface;
use Drupal\facets\Result\Result;

/**
 * A processor that orders the results by raw value.
 *
 * @FacetsProcessor(
 *   id = "raw_value_widget_order",
 *   label = @Translation("Sort by raw value"),
 *   description = @Translation("Sorts the widget results by raw value."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class RawValueWidgetOrderProcessor extends WidgetOrderPluginBase implements WidgetOrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function sortResults(array $results, $order = 'ASC') {
    if ($order === 'ASC') {
      usort($results, 'self::sortRawValueAsc');
    }
    else {
      usort($results, 'self::sortRawValueDesc');
    }

    return $results;
  }

  /**
   * Sorts ascending.
   */
  protected static function sortRawValueAsc(Result $a, Result $b) {
    return strnatcasecmp($a->getRawValue(), $b->getRawValue());
  }

  /**
   * Sorts descending.
   */
  protected static function sortRawValueDesc(Result $a, Result $b) {
    return strnatcasecmp($b->getRawValue(), $a->getRawValue());
  }

}

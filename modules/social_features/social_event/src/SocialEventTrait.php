<?php

namespace Drupal\social_event;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;

/**
 * Trait SocialEventTrait.
 *
 * @package Drupal\social_event
 */
trait SocialEventTrait {

  /**
   * Function to determine if an event has been finished.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event.
   *
   * @return bool
   *   TRUE if the evens is finished/completed.
   */
  protected function eventHasBeenFinished(NodeInterface $node): bool {
    $current_time = new DrupalDateTime();

    // Use the start date when the end date is not set to determine if the event
    // is closed.
    /** @var \Drupal\Core\Datetime\DrupalDateTime $check_end_date */
    $check_end_date = $node->get('field_event_date_end')->isEmpty()
      ? $node->get('field_event_date')->date
      : $node->get('field_event_date_end')->date;

    if (!$check_end_date instanceof DrupalDateTime) {
      // Not possible to detect end date.
      return FALSE;
    }

    // Check if is activated all_day checkbox.
    $check_all_day = !$node->get('field_event_all_day')->isEmpty()
      ? $node->get('field_event_all_day')->getString()
      : NULL;

    // The event has finished if the end date is smaller than the current date,
    // and if the all day checkbox isn't activated,
    // and the end date is not equal to the current date.
    return $current_time->getTimestamp() > $check_end_date->getTimestamp() &&
      !($check_all_day && $check_end_date->format('Y-m-d') === $current_time->format('Y-m-d'));
  }

}

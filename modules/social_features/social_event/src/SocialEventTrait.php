<?php

namespace Drupal\social_event;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;

/**
 * Trait SocialEventTrait.
 *
 * @package Drupal\social_event
 */
trait SocialEventTrait {

  /**
   * Function to determine if an event has been finished.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The event.
   *
   * @return bool
   *   TRUE if the evens is finished/completed.
   */
  protected function eventHasBeenFinished(Node $node): bool {
    $current_time = new DrupalDateTime();

    // Use the start date when the end date is not set to determine if the event
    // is closed.
    /** @var \Drupal\Core\Datetime\DrupalDateTime $check_end_date */
    $check_end_date = $node->field_event_date_end->date ?? $node->field_event_date->date;

    // The event has finished if the end date is smaller than the current date.
    return $current_time > $check_end_date;
  }

}

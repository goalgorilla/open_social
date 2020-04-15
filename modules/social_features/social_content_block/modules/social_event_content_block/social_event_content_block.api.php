<?php

/**
 * @file
 * Hooks for the Social Event Content Block module.
 */

/**
 * Change the filter range for a content list block of events.
 *
 * @param array $range
 *   An array containing a `start` and `end` value that can be set to control
 *   the start and end dates of the result.
 * @param string $value
 *   The value that the user chose in the date filter value.
 */
function hook_social_event_content_block_date_range_alter(array &$range, $value) {
  // We only care about the 'example' choice, other choices are handled by other
  // modules.
  if ($value === 'example') {
    // Only show events that occur on april 1st of this year.
    $range['start'] = new DateTime('1 april this year 00:00');
    $range['end'] = new DateTime('1 april this year 23:59');
  }
}

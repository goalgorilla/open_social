<?php

namespace Drupal\facets\Utility;

use Drupal\Core\Datetime\DateFormatter;

/**
 * Dates Handler service.
 */
class FacetsDateHandler {

  /**
   * String that represents a time gap of a day between two dates.
   */
  const FACETS_DATE_DAY = 'DAY';

  /**
   * String that represents a time gap of a year between two dates.
   */
  const FACETS_DATE_YEAR = 'YEAR';

  /**
   * String that represents a time gap of a month between two dates.
   */
  const FACETS_DATE_MONTH = 'MONTH';

  /**
   * String that represents a time gap of an hour between two dates.
   */
  const FACETS_DATE_HOUR = 'HOUR';

  /**
   * String that represents a time gap of a minute between two dates.
   */
  const FACETS_DATE_MINUTE = 'MINUTE';

  /**
   * String that represents a time gap of a second between two dates.
   */
  const FACETS_DATE_SECOND = 'SECOND';

  /**
   * Date string for ISO 8601 date formats.
   */
  const FACETS_DATE_ISO8601 = 'Y-m-d\TH:i:s\Z';

  /**
   * Regex pattern for range queries.
   */
  const FACETS_REGEX_RANGE = '/^[\[\{](\S+) TO (\S+)[\]\}]$/';

  /**
   * Regex pattern for date queries.
   */
  const FACETS_REGEX_DATE = '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/';

  /**
   * Regex pattern for date ranges.
   */
  const FACETS_REGEX_DATE_RANGE = '/^\[((\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z) TO ((\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z)\]$/';

  /**
   * The date formatting service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * FacetsDateHandler constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatting service.
   */
  public function __construct(DateFormatter $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Converts dates from Unix timestamps into ISO 8601 format.
   *
   * @param int $timestamp
   *   An integer containing the Unix timestamp being converted.
   * @param string $gap
   *   A string containing the gap, see FACETS_DATE_* constants for valid
   *   values. Defaults to FACETS_DATE_SECOND.
   *
   * @return string
   *   A string containing the date in ISO 8601 format.
   */
  public function isoDate($timestamp, $gap = 'SECOND') {
    switch ($gap) {
      case static::FACETS_DATE_SECOND:
        $format = static::FACETS_DATE_ISO8601;
        break;

      case static::FACETS_DATE_MINUTE:
        $format = 'Y-m-d\TH:i:00\Z';
        break;

      case static::FACETS_DATE_HOUR:
        $format = 'Y-m-d\TH:00:00\Z';
        break;

      case static::FACETS_DATE_DAY:
        $format = 'Y-m-d\T00:00:00\Z';
        break;

      case static::FACETS_DATE_MONTH:
        $format = 'Y-m-01\T00:00:00\Z';
        break;

      case static::FACETS_DATE_YEAR:
        $format = 'Y-01-01\T00:00:00\Z';
        break;

      default:
        $format = static::FACETS_DATE_ISO8601;
        break;
    }
    return gmdate($format, $timestamp);
  }

  /**
   * Return a date gap one increment smaller than the one passed.
   *
   * @param string $gap
   *   A string containing the gap, see FACETS_DATE_* constants for valid
   *   values.
   * @param string $min_gap
   *   A string containing the the minimum gap that can be returned, defaults to
   *   FACETS_DATE_SECOND. This is useful for defining the smallest increment
   *   that can be used in a date drilldown.
   *
   * @return string
   *   A string containing the smaller date gap, NULL if there is no smaller
   *   gap. See FACETS_DATE_* constants for valid values.
   */
  public function getNextDateGap($gap, $min_gap = self::FACETS_DATE_SECOND) {
    // Array of numbers used to determine whether the next gap is smaller than
    // the minimum gap allowed in the drilldown.
    $gap_numbers = array(
      static::FACETS_DATE_YEAR => 6,
      static::FACETS_DATE_MONTH => 5,
      static::FACETS_DATE_DAY => 4,
      static::FACETS_DATE_HOUR => 3,
      static::FACETS_DATE_MINUTE => 2,
      static::FACETS_DATE_SECOND => 1,
    );

    // Gets gap numbers for both the gap and minimum gap, checks if the next gap
    // is within the limit set by the $min_gap parameter.
    $gap_num = isset($gap_numbers[$gap]) ? $gap_numbers[$gap] : 6;
    $min_num = isset($gap_numbers[$min_gap]) ? $gap_numbers[$min_gap] : 1;
    return ($gap_num > $min_num) ? array_search($gap_num - 1, $gap_numbers) : $min_gap;
  }

  /**
   * Determines the best search gap to use for an arbitrary date range.
   *
   * Generally, we use the maximum gap that fits between the start and end date.
   * If they are more than a year apart, 1 year; if they are more than a month
   * apart, 1 month; etc.
   *
   * This function uses Unix timestamps for its computation and so is not useful
   * for dates outside that range.
   *
   * @param int $start_time
   *   A string containing the start date as an ISO date string.
   * @param int $end_time
   *   A string containing the end date as an ISO date string.
   * @param string|NULL $min_gap
   *   (Optional) The minimum gap that should be returned.
   *
   * @return string
   *   A string containing the gap, see FACETS_DATE_* constants for valid
   *   values. Returns FALSE of either of the dates cannot be converted to a
   *   timestamp.
   */
  public function getTimestampGap($start_time, $end_time, $min_gap = NULL) {
    $time_diff = $end_time - $start_time;
    switch (TRUE) {
      case ($time_diff >= 31536000):
        $gap = static::FACETS_DATE_YEAR;
        break;

      case ($time_diff >= 86400 * gmdate('t', $start_time)):
        $gap = static::FACETS_DATE_MONTH;
        break;

      case ($time_diff >= 86400):
        $gap = static::FACETS_DATE_DAY;
        break;

      case ($time_diff >= 3600):
        $gap = static::FACETS_DATE_HOUR;
        break;

      case ($time_diff >= 60):
        $gap = static::FACETS_DATE_MINUTE;
        break;

      default:
        $gap = static::FACETS_DATE_SECOND;
        break;
    }

    // Return the calculated gap if a minimum gap was not passed of the
    // calculated gap is a larger interval than the minimum gap.
    if (is_null($min_gap) || $this->gapCompare($gap, $min_gap) >= 0) {
      return $gap;
    }
    else {
      return $min_gap;
    }
  }

  /**
   * Converts ISO date strings to Unix timestamps.
   *
   * Passes values to the FACETS_get_timestamp_gap() function to calculate the
   * gap.
   *
   * @param string $start_date
   *   A string containing the start date as an ISO date string.
   * @param string $end_date
   *   A string containing the end date as an ISO date string.
   * @param string|NULL $min_gap
   *   (Optional) The minimum gap that should be returned.
   *
   * @return string
   *   A string containing the gap, see FACETS_DATE_* constants for valid
   *   values. Returns FALSE of either of the dates cannot be converted to a
   *   timestamp.
   *
   * @see FACETS_get_timestamp_gap()
   */
  public function getDateGap($start_date, $end_date, $min_gap = NULL) {
    $range = array(strtotime($start_date), strtotime($end_date));
    if (!in_array(FALSE, $range, TRUE)) {
      return $this->getTimestampGap($range[0], $range[1], $min_gap);
    }
    return FALSE;
  }

  /**
   * Returns a formatted date based on the passed timestamp and gap.
   *
   * This function assumes that gaps less than one day will be displayed in a
   * search context in which a larger containing gap including a day is already
   * displayed. So, HOUR, MINUTE, and SECOND gaps only display time information,
   * without date.
   *
   * @param int $timestamp
   *   An integer containing the Unix timestamp.
   * @param string $gap
   *   A string containing the gap, see FACETS_DATE_* constants for valid
   *   values, defaults to YEAR.
   *
   * @return string
   *   A gap-appropriate display date used in the facet link.
   */
  public function formatTimestamp($timestamp, $gap = self::FACETS_DATE_YEAR) {
    switch ($gap) {
      case static::FACETS_DATE_MONTH:
        return $this->dateFormatter->format($timestamp, 'custom', 'F Y', 'UTC');

      case static::FACETS_DATE_DAY:
        return $this->dateFormatter->format($timestamp, 'custom', 'F j, Y', 'UTC');

      case static::FACETS_DATE_HOUR:
        return $this->dateFormatter->format($timestamp, 'custom', 'g A', 'UTC');

      case static::FACETS_DATE_MINUTE:
        return $this->dateFormatter->format($timestamp, 'custom', 'g:i A', 'UTC');

      case static::FACETS_DATE_SECOND:
        return $this->dateFormatter->format($timestamp, 'custom', 'g:i:s A', 'UTC');

      default:
        return $this->dateFormatter->format($timestamp, 'custom', 'Y', 'UTC');
    }
  }

  /**
   * Returns a formatted date based on the passed ISO date string and gap.
   *
   * @param string $date
   *   A string containing the date as an ISO date string.
   * @param int $gap
   *   An integer containing the gap, see FACETS_DATE_* constants for valid
   *   values, defaults to YEAR.
   * @param string $callback
   *   The formatting callback, defaults to "FACETS_format_timestamp". This is
   *   a string that can be called as a valid callback.
   *
   * @return string
   *   A gap-appropriate display date used in the facet link.
   *
   * @see FACETS_format_timestamp()
   */
  public function formatDate($date, $gap = self::FACETS_DATE_YEAR, $callback = 'facets_format_timestamp') {
    $timestamp = strtotime($date);
    return $callback($timestamp, $gap);
  }

  /**
   * Returns the next increment from the given ISO date and gap.
   *
   * This function is useful for getting the upper limit of a date range from
   * the given start date.
   *
   * @param string $date
   *   A string containing the date as an ISO date string.
   * @param string $gap
   *   A string containing the gap, see FACETS_DATE_* constants for valid
   *   values, defaults to YEAR.
   *
   * @return string
   *   A string containing the date, FALSE if the passed date could not be
   *   parsed.
   */
  public function getNextDateIncrement($date, $gap) {
    if (preg_match(static::FACETS_REGEX_DATE, $date, $match)) {

      // Increments the timestamp.
      switch ($gap) {
        case static::FACETS_DATE_MONTH:
          $match[2] += 1;
          break;

        case static::FACETS_DATE_DAY:
          $match[3] += 1;
          break;

        case static::FACETS_DATE_HOUR:
          $match[4] += 1;
          break;

        case static::FACETS_DATE_MINUTE:
          $match[5] += 1;
          break;

        case static::FACETS_DATE_SECOND:
          $match[6] += 1;
          break;

        default:
          $match[1] += 1;
          break;

      }

      // Gets the next increment.
      return $this->isoDate(
        gmmktime($match[4], $match[5], $match[6], $match[2], $match[3], $match[1])
      );
    }
    return FALSE;
  }

  /**
   * Compares two timestamp gaps.
   *
   * @param int $gap1
   *   An integer containing the gap, see FACETS_DATE_* constants for valid
   *   values.
   * @param int $gap2
   *   An integer containing the gap, see FACETS_DATE_* constants for valid
   *   values.
   *
   * @return int
   *   Returns -1 if gap1 is less than gap2, 1 if gap1 is greater than gap2, and
   *   0 if they are equal.
   */
  public function gapCompare($gap1, $gap2) {

    $gap_numbers = array(
      static::FACETS_DATE_YEAR => 6,
      static::FACETS_DATE_MONTH => 5,
      static::FACETS_DATE_DAY => 4,
      static::FACETS_DATE_HOUR => 3,
      static::FACETS_DATE_MINUTE => 2,
      static::FACETS_DATE_SECOND => 1,
    );

    $gap1_num = isset($gap_numbers[$gap1]) ? $gap_numbers[$gap1] : 6;
    $gap2_num = isset($gap_numbers[$gap2]) ? $gap_numbers[$gap2] : 6;

    if ($gap1_num == $gap2_num) {
      return 0;
    }
    else {
      return ($gap1_num < $gap2_num) ? -1 : 1;
    }
  }

  /**
   * Extracts "start" and "end" dates from an active items.
   *
   * @param string $item
   *   The active item to extract the dates.
   *
   * @return mixed
   *   Returns FALSE if no item found and an array with the dates if the dates
   *    were extracted as expected.
   */
  public function extractActiveItems($item) {
    $active_item = [];
    if (preg_match(static::FACETS_REGEX_DATE_RANGE, $item, $matches)) {

      $active_item['start'] = [
        'timestamp' => strtotime($matches[1]),
        'iso' => $matches[1],
      ];

      $active_item['end'] = [
        'timestamp' => strtotime($matches[8]),
        'iso' => $matches[8],
      ];

      return $active_item;
    }
    return FALSE;
  }

}

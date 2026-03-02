/**
 * @file
 * Customizes the FullCalendar day header format for event meeting scheduler.
 */

((Drupal, drupalSettings) => {
  'use strict';

  /**
   * Formats date to "Wed 2/2" format.
   *
   * @param {Date} date
   *   The date to format.
   *
   * @return {string}
   *   Formatted date string.
   */
  function formatDayHeader(date) {
    const dayName = new Intl.DateTimeFormat(
      drupalSettings.path?.currentLanguage || 'en',
      { weekday: 'short' }
    ).format(date);
    const month = date.getMonth() + 1; // getMonth() returns 0-11
    const day = date.getDate();
    return `${dayName} ${month}/${day}`;
  }

  /**
   * Updates calendar instances with custom day header format.
   *
   * @return {boolean}
   *   TRUE if calendars were updated, FALSE otherwise.
   */
  function updateCalendars() {
    if (!Drupal.meetingPeriodScheduleInstances || Drupal.meetingPeriodScheduleInstances.size === 0) {
      return false;
    }

    // Iterate through all calendar instances.
    Drupal.meetingPeriodScheduleInstances.forEach((calendar) => {
      // Update dayHeaderContent option to use a custom format.
      calendar.setOption('dayHeaderContent', (arg) => {
        return formatDayHeader(arg.date);
      });
    });

    return true;
  }

  /**
   * Customizes a calendar day header format.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Updates FullCalendar instances with custom day header format.
   */
  Drupal.behaviors.eventMeetingScheduler = {
    attach(context) {
      // Check if calendars are already initialized.
      if (updateCalendars()) {
        return;
      }

      // Wait for period-schedule behavior to initialize calendars.
      // Check for calendar elements in the DOM and retry if needed.
      const checkInterval = setInterval(() => {
        if (updateCalendars()) {
          clearInterval(checkInterval);
        }
      }, 50);

      // Stop checking after 1 second to avoid infinite loops.
      setTimeout(() => {
        clearInterval(checkInterval);
      }, 1000);
    },
  };
})(Drupal, drupalSettings);

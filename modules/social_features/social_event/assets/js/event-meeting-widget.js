/**
 * @file
 * Event Meeting Widget JavaScript functionality.
 */

(function (Drupal, once) {

  'use strict';

  /**
   * Behaviors for Event Meeting Widget.
   */
  Drupal.behaviors.eventMeetingWidget = {
    attach: function (context, settings) {
      // Process all spans with meeting type data attributes.
      const spans = once('event-meeting-widget-click', 'span[data-meeting-type]', context);

      // When a user clicks on the "span" with meeting a label, we trigger
      // the ajax callback attached to the "meeting_type" form element.
      spans.forEach(function (span) {
        span.addEventListener('click', function (e) {
          e.preventDefault();

          const isSelected = span.dataset.isSelected;
          if (isSelected === 'true') {
            return;
          }

          // Make sure we have a meeting type.
          const meetingType = span.dataset.meetingType;
          if (!meetingType) {
            return;
          }

          const widget = span.closest('.event-meeting-widget');
          if (!widget) {
            return;
          }

          const radios = widget.querySelector('.meeting-type-selector');
          if (!radios) {
            return;
          }

          // Find the corresponding radio input with the same value.
          const radioInput = radios.querySelector('input[type="radio"][value="' + meetingType + '"]')

          if (radioInput) {
            // Trigger click on the radio input to select it.
            radioInput.click();

            // Add UI indicators to notify the user that something is happening.
            const allSpans = widget.querySelectorAll('span[data-meeting-type]');
            allSpans.forEach(function (spanElement) {
              spanElement.setAttribute('data-is-selected', 'false');
            });
            span.setAttribute('data-is-selected', 'true');

            widget.querySelector('.meeting-type-wrapper')
              .setAttribute('style', 'opacity: 0.6;');
          }
        });
      });
    }
  };

})(Drupal, once);

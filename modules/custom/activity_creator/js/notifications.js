/**
 * @file
 * Update the notification bell badge.
 */

(function ($, Drupal) {
  /**
   * Notification centre bell update behavior.
   */
  Drupal.behaviors.notificationUpdate = {
    attach: function (context) {
      $('.notification-bell', context)
        .once('notificationUpdate')
        .click(this._updateNotificationCount);
    },

    _updateNotificationCount: function () {
      var $notificationCount = $('.notification-bell .badge');

      // We won't proceed if the notification count is 0 or the dropdown is
      // already open.
      // @todo: Refactor to use data-* attributes and `jQuery.data()` to store
      //   the unread notification count. This should be less expensive than
      //   reading the DOM
      if (!$notificationCount.first().text() || $notificationCount.first().text() === '0' || $(this).hasClass('open')) {
        return;
      }

      // Post to the notification endpoint.
      $.ajax({
        method: 'POST',
        url: '/ajax/notifications-mark-as-read',
        data: { },
        success: function (result) {
          // Update the notification bell.
          $notificationCount.text(result['remaining_notifications']);
        }
      });
    }
  };
})(jQuery, Drupal);

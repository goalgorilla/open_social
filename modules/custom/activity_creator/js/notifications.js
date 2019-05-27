/**
 * @file
 * Update the notification bell badge.
 */

(function ($) {
  /**
   * Notification centre bell update behavior.
   */
  Drupal.behaviors.notificationUpdate = {
    attach: function (context, settings) {

      // @todo: Refactor to use data-* attributes and `jQuery.data()` to store
      //   the unread notification count.
      $('.notification-bell', context).once('notificationUpdate').click(function (e) {
        var $notificationCount = $('.notification-bell .badge');

        // We won't proceed if the notification count is 0 or the dropdown is
        // open.
        if (!$notificationCount.html() || $notificationCount.html() === '0' || $(this).hasClass('open')) {
          return;
        }

        // Post to the notification endpoint.
        $.ajax({
          method: 'POST',
          url: '/ajax/notifications-mark-as-read',
          data: { },
          success: function (result) {
            // Update the notification bell.
            var $remainingNotifications = result['remaining_notifications'];

            $notificationCount.html($remainingNotifications);
          }
        });
      });

    }
  };
})(jQuery);

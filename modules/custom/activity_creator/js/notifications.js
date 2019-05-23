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

      $('.notification-bell', context).once('notificationUpdate').click(function(e) {
        var notification_count = $('.notification-bell .badge');

        // We won't proceed if the notification count is 0 or the dropdown is
        // open.
        if (!notification_count.html() || notification_count.html() === "0" || $(this).hasClass('open')) {
          return;
        }

        // Post to the notification endpoint.
        $.ajax({
          method: 'POST',
          url: '/ajax/notifications-mark-as-read',
          data: { },
          success: function(result) {
            // Update the notification bell.
            var remaining_notifications = result['remaining_notifications'];

            notification_count.html(remaining_notifications);
            $('.notification-bell.mobile .badge').html(remaining_notifications);
          }
        });
      });

    }
  };
})(jQuery);

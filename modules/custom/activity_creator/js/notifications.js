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
        var $notificationCount = $('.notification-bell .badge');

        // We won't proceed if the notification count is 0 or the dropdown is
        // open.
        if (!$notificationCount.html() || $notificationCount.html() === "0" || $(this).hasClass('open')) {
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

            $notificationCount.html(remaining_notifications);
            $('.notification-bell.mobile .badge').html(remaining_notifications);
          }
        });
      });

    }
  };
})(jQuery);
https://github.com/goalgorilla/open_social/pull/1405#discussion_r287411473
/**
 * @file
 * Update the notification bell badge.
 */

(function ($, Drupal, once) {
  /**
   * Notification Center bell update behavior.
   */
  Drupal.behaviors.notificationUpdate = {
    attach: function (context) {
      const $notificationUpdateOnce = $(once('notificationUpdate', '.notification-bell', context));
      $notificationUpdateOnce.click(this._updateNotificationCount);
    },

    _updateNotificationCount: function () {

      // We won't proceed if the dropdown is already open.
      if ($(this).hasClass('open')) {
        return;
      }

      // Post to the notification endpoint.
      $('.dropdown-menu a', this).first().click();
    }
  };
})(jQuery, Drupal, once);

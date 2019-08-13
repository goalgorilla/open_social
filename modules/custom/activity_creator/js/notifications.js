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

      // We won't proceed if the notification count is 0 or the dropdown is
      // already open.
      // @todo: Refactor to use data-* attributes and `jQuery.data()` to store
      //   the unread notification count. This should be less expensive than
      //   reading the DOM
      if ($(this).hasClass('open')) {
        return;
      }

      // Post to the notification endpoint.
      $('.dropdown-menu a', this).first().click();
    }
  };
})(jQuery, Drupal);

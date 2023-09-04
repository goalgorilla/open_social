/**
 * @file
 * Scripts for the invite forms.
 */
(function (Drupal, $, once) {

  'use strict';

  Drupal.behaviors.socialInviteForm = {
    attach: function (context) {
      $(once(
        'socialInviteFormUserInput',
        $('[data-drupal-selector="enroll-invite-email-form"]', context)
          .find('[data-drupal-selector="edit-users-fieldset-user"]')
      )).each(function () {
        var select2Config = $(this).data('select2Config') || {};
        select2Config.insertTag = (data, tag) => {
          var pos = tag.text.indexOf('@');

          if (pos !== -1 && pos + 1 !== tag.text.length) {
            data.push(tag);
          }
        };
      });
    }
  };

})(Drupal, jQuery, once);

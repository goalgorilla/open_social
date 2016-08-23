/**
 * @file
 */

(function ($) {
  Drupal.node_edit_protection = {};
  // Allow Submit/Cancel buttons.
  var submit = false;
  Drupal.behaviors.nodeEditProtection = {
    attach: function (context) {
      // Click on "Cancel Account" button.
      $('#edit-delete', context).click(function () {
        submit = true;
      });
      // Click on "Save" button.
      $('#edit-submit', context).click(function () {
        var passValue = $('#edit-pass-pass1', context).val();
        var passConfirmValue = $('#edit-pass-pass2', context).val();
        if (passValue && passConfirmValue) {
          submit = true;
        }
      });

      // Handle back button, exit etc.
      window.onbeforeunload = function () {
        if (!submit) {
          return (Drupal.t('You need to set your password.'));
        }
      }
    }
  };
})(jQuery);

(function ($, Drupal) {

  Drupal.behaviors.validator = {
    attach: function (context, settings) {
      if ($('#private-message-add-form').length == 0) {
        return;
      }

      var check = 0;

      var handler = setInterval(function () {
        var selector = '#edit-members-wrapper input[type="search"]';

        if ($(selector).length > 0 || check > 100) {
          clearInterval(handler);

          if ($(selector).length > 0) {
            $(selector).eq(0).attr('id', 'members').attr('required', 'required');

            document.getElementById('members').addEventListener('invalid', function () {
              this.setCustomValidity('Warning');
            });
          }
        }

        check++;
      }, 10);
    }
  }

})(jQuery, Drupal);

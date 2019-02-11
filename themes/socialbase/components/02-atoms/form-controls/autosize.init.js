(function ($) {

  Drupal.behaviors.textareaAutogrow = {
    attach: function (context, settings) {

      // Attach autosize listener.
      $(".form-control--autogrow", context).once("textareaAutogrow").each(function () {
        autosize.destroy($('.form-control--autogrow'));
        autosize($('.form-control--autogrow'));
        autosize.update($('.form-control--autogrow'));
      });

    }
  }

  Drupal.behaviors.textareaFocus = {
    attach: function (context, settings) {
      $('textarea:last').focusin(function() {
        $(this).parents('.main-container').toggleClass('open-keyboard');
      });
      $('textarea:last').focusout(function() {
        $(this).parents('.main-container').toggleClass('open-keyboard');
      });
    }
  }

})(jQuery);

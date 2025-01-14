(function (Drupal, $, once) {

  Drupal.behaviors.textareaAutogrow = {
    attach: function (context, settings) {

      // Attach autosize listener.
      $(once('textareaAutogrow', ".form-control--autogrow", context)).each(function () {
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

})(Drupal, jQuery, once);

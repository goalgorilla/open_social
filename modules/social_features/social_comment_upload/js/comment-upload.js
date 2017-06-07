(function ($) {

  'use strict';

  Drupal.behaviors.socialCommentUpload = {
    attach: function(context, setting) {
      $('.comment-attachments').once('socialCommentUpload').each(function () {
        var $content = $('> div', this).filter(function () {
              return !$(this).hasClass('panel-heading');
            }),
            $header = $('> summary', this),
            display = true,
            handle = function () {
              display = !display;
              $content.toggle(display);
            };

        $header
          .on('click', handle)
          .ready(handle);
      });
    }
  };

})(jQuery);

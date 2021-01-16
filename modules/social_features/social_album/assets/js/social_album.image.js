(function ($, Drupal) {
  Drupal.behaviors.socialAlbumImage = {
    attach: function attach(context) {
      var init = true;

      $(context).find('.social-post-album--popup-slider-wrapper').on('afterChange', function() {
        if (init) {
          $(this)
            .closest('.social-post-album--popup')
            .find('.form-actions .btn')
            .removeClass('hide');

          init = false;
        }

        var index = $(this)
          .closest('.social-post-album--popup')
          .find('.post-comment-block--active')
          .data('active-post');

        var $button = $(this)
          .closest('.ui-dialog')
          .find('.ui-dialog-buttonset .' + index);

        if ($button.hasClass('hide')) {
          $button.parent().find('.ui-button').addClass('hide');
          $button.removeClass('hide');
        }
      });
    }
  };
})(jQuery, Drupal);

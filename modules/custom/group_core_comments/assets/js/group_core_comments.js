(function ($, Drupal) {
  Drupal.behaviors.groupCoreComments = {
    attach: function (context) {
      $(once('forbidden-post-comments', '.forbidden-post-comments-wrapper', context)).each(function () {
        let forbiddenPost = $(this);
        let popup = forbiddenPost.find('.popup-info', context);
        let popupH = popup.outerHeight();
        let link = forbiddenPost.find('.description .btn-action__group');
        console.log(link);
        popup.css('top', (-popupH - 5));

        $(window).on('resize', function () {
          let popupHResize = popup.outerHeight();
          popup.css('top', (-popupHResize - 5));
        });

        link.on('click', function (event) {
          event.preventDefault();
          $(this).toggleClass('open');
        });

        $(document).click(function (event) {
          if ($(event.target).closest('.forbidden-post-comments-wrapper .description .btn-action__group').length) {
            return;
          }
          if ($(event.target).closest('.forbidden-post-comments-wrapper .popup-info').length) {
            return;
          }
          link.removeClass('open');
          event.stopPropagation();
        });
      });
    }
  };
})(jQuery, Drupal);
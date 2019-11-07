(function ($) {
  Drupal.behaviors.groupCoreComments = {
    attach: function (context) {
      var forbiddenPost = $('.forbidden-post-comments-wrapper', context);
      var popup = forbiddenPost.find('.popup-info', context);
      var popupH = popup.outerHeight();
      var link = forbiddenPost.find('.description .btn-action__group');

      popup.css('top', (-popupH - 5));

      $(window).on('resize', function () {
        var popupHResize = popup.outerHeight();
        popup.css('top', (-popupHResize - 5));
      });

      link.on('click', function (e) {
        e.preventDefault();
        $(this).toggleClass('open');
      });

      $(document).click(function(event) {
        if ($(event.target).closest('.forbidden-post-comments-wrapper .description .btn-action__group').length) return;
        if ($(event.target).closest('.forbidden-post-comments-wrapper .popup-info').length) return;
        link.removeClass('open');
        event.stopPropagation();
      });
    }
  };
})(jQuery);

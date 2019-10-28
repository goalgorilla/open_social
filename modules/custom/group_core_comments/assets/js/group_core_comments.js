(function ($) {
  Drupal.behaviors.groupCoreComments = {
    attach: function (context, settings) {
      var forbiddenPost = $('.forbidden-post-comments-wrapper');
      var popup = forbiddenPost.find('.popup-info');
      var popupH = popup.outerHeight();
      var link = forbiddenPost.find('.description > a');

      popup.css('top', (-popupH + 20));

      link.on('click', function (e) {
        e.preventDefault();
        popup.toggleClass('open');
      });
    }
  };
})(jQuery);

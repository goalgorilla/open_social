(function ($) {
  Drupal.behaviors.socialPostAlbumPopupSlider = {
    attach: function () {
      var albumSlider = $('.social-post-album--popup-slider-wrapper');

      // Init slick slider.
      albumSlider.on('init', function(){
        var activeIdPost = $('.slick-active').attr('data-active-post');
        var postCommentBlock = $('.post-comment-wrapper');

        postCommentBlock.removeClass('post-comment-block--active');
        postCommentBlock.filter('[data-active-post='+ activeIdPost +']').addClass('post-comment-block--active');
      });

      albumSlider.slick({
        infinite: true,
        nextArrow: '<button type="button" data-role="none" class="slick-next slick-arrow" aria-label="Next" role="button">' +
          '<svg><use xlink:href="#arrow-forward"></use></svg>' +
          '</button>',
        prevArrow: '<button type="button" data-role="none" class="slick-prev slick-arrow" aria-label="Previous" role="button">' +
          '<svg><use xlink:href="#arrow-back"></use></svg>' +
          '</button>',
      }).on('afterChange', function(){
        var activeIdPost = $('.slick-active').attr('data-active-post');
        var postCommentBlock = $('.post-comment-wrapper');

        postCommentBlock.removeClass('post-comment-block--active');
        postCommentBlock.filter('[data-active-post='+ activeIdPost +']').addClass('post-comment-block--active');
      });
    }
  };
})(jQuery);

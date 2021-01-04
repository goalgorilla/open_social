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

        setTimeout(function () {
          var postHeaderHeight = postCommentBlock.find('.post-header').outerHeight() + 10;

          postCommentBlock.find('.card .card__block').css('padding-top', postHeaderHeight);
        }, 0);
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

      var albumPopup = $('.social-post-album--popup');

      if (albumPopup.length) {


        // Disabled scroll when slider popup is opened.
        var $docEl = $('html, body');
        var $wrap = $('.dialog-off-canvas-main-canvas');
        var scrollTop;

        function lockBody() {
          scrollTop = $docEl.scrollTop();

          $wrap.css({
            top: -(scrollTop),
            position: 'relative' });

          $docEl.css({
            height: '100%',
            overflow: 'hidden' });
        }
        function unlockBody() {
          $docEl.css({
            height: '',
            overflow: '' });

          $wrap.css({ top: '', position: '' });

          window.scrollTo(0, scrollTop);
          window.setTimeout(() => {
            scrollTop = null;
          }, 0);
        }
        function popupClose() {
          unlockBody();
        }
        function popupOpen() {
          lockBody();
        }

        popupOpen();

        setTimeout(function () {

          var closeBtn = $('.ui-dialog-titlebar-close');

          closeBtn.on('click', function () {
            popupClose();
          });
        }, 3000);

        $('.social-post-album--popup-slider, .social-post-album--popup-item').on('click', function(event) {
          if ($(event.target).closest('.social-post-album--popup-item > img, .post-comment-wrapper, .slick-arrow').length) return;
          popupClose();
          $('.ui-dialog, .ui-widget-overlay').remove();
          event.stopPropagation();
        });
      }
    }
  };
})(jQuery);

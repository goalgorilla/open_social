(function ($, Drupal) {
  Drupal.behaviors.socialPostAlbumPopupSlider = {
    attach: function (context) {

      // Run scripts if Drupal.dialog is created.
      $(window).on('dialog:aftercreate', function (dialog, $element, settings) {
        var albumSlider = $(context).find('.social-post-album--popup-slider-wrapper');

        albumSlider.once('socialPostAlbumPopupSlider').each(function () {
          var el = $(this),
              albumPopup = el.closest('.social-post-album--popup'),
              slickInitClass = '.slick-initialized';

          // Init slick slider.
          el.not(slickInitClass).on('init', function () {
            // Check `post-index` current image and show post comment block
            // with the same `post-index` on `init` slider.
            var activeIdPost = el.find('.slick-active').attr('data-active-post');
            var postCommentBlock = albumPopup.find('.post-comment-wrapper');

            postCommentBlock.removeClass('post-comment-block--active');

            var activePost = postCommentBlock.filter('[data-active-post=' + activeIdPost + ']');
            activePost.addClass('post-comment-block--active');
          });

          // Slick settings.
          el.not(slickInitClass).slick({
            infinite: true,
            // Add custom navigation arrows to the slick slider.
            nextArrow: '<button type="button" data-role="none" class="slick-next slick-arrow" aria-label="Next" role="button">' +
              '<svg><use xlink:href="#arrow-forward"></use></svg>' +
              '</button>',
            prevArrow: '<button type="button" data-role="none" class="slick-prev slick-arrow" aria-label="Previous" role="button">' +
              '<svg><use xlink:href="#arrow-back"></use></svg>' +
              '</button>',
          }).on('afterChange', function () {
            // Check `post-index` current image and show post comment block
            // with the same `post-index` on `afterChange` slider.
            var activeIdPost = el.find('.slick-active').attr('data-active-post');
            var postCommentBlock = albumPopup.find('.post-comment-wrapper');

            postCommentBlock.removeClass('post-comment-block--active');

            var activePost = postCommentBlock.filter('[data-active-post=' + activeIdPost + ']');
            activePost.addClass('post-comment-block--active');
          });

          if (albumPopup.length) {
            // Disable/Enable scroll when slider popup is opened/closed.
            var $docEl = $('html, body'),
                $wrap = $('.dialog-off-canvas-main-canvas'),
                scrollTop;

            function lockBody() {
              scrollTop = $docEl.scrollTop();

              $wrap.css({
                top: -(scrollTop),
                position: 'relative'
              });

              $docEl.css({
                height: '100%',
                overflow: 'hidden'
              });
            }

            function unlockBody() {
              $docEl.css({
                height: '',
                overflow: ''
              });

              $wrap.css({ top: '', position: '' });
              window.scrollTo(0, scrollTop);
              scrollTop = null;
            }

            // Disable page scroll and save page scroll position after create popup.
            lockBody();

            var closeBtn = $('.ui-dialog-titlebar-close');

            closeBtn.on('click', function () {
              // Enable page scroll and save page scroll position after close popup.
              unlockBody();
            });

            // Disable page scroll if you click on empty space around an image inside popup
            $('.social-post-album--popup-slider, .social-post-album--popup-item').on('click', function (event) {
              if ($(event.target).closest('.social-post-album--popup-item > img, .post-comment-wrapper, .slick-arrow').length) return;
              unlockBody();
              $('.ui-dialog, .ui-widget-overlay').remove();
              event.stopPropagation();
            });
          }
        });

      });
    }
  };
})(jQuery, Drupal);

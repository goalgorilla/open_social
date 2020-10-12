(function ($) {
  Drupal.behaviors.socialPostAlbumPopupSlider = {
    attach: function () {
      var albumSlider = $('.social-post-album--popup-slider-wrapper');

      // Init slick slider.
      albumSlider.slick({
        infinite: true,
        nextArrow: '<button type="button" data-role="none" class="slick-next slick-arrow" aria-label="Next" role="button">' +
          '<svg><use xlink:href="#arrow-forward"></use></svg>' +
          '</button>',
        prevArrow: '<button type="button" data-role="none" class="slick-prev slick-arrow" aria-label="Previous" role="button">' +
          '<svg><use xlink:href="#arrow-back"></use></svg>' +
          '</button>',
      });
    }
  };
})(jQuery);

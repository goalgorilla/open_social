(function (Drupal, $, once) {

  'use strict';

  Drupal.behaviors.socialCommentUpload = {
    attach: function(context, setting) {

      // The once call is wrapped in $() to allow the usual jQuery chaining.
      const $socialCommentUploadOnce = $(once('socialCommentUpload', '.comment-attachments'));
      // `$elements` is always a jQuery object.
      $socialCommentUploadOnce.each(function () {
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

  Drupal.behaviors.socialCommentUploadPhotoGalleryCustom = {
    attach: function(context, setting) {

      var $pswp = $('.pswp')[0];
      var image = [];

      // The once call is wrapped in $() to allow the usual jQuery chaining.
      const $AttachGalleryToPhotoswipeElement =  $(once('AttachGalleryToPhotoswipeElement', '.photoswipe-gallery-custom'));
      // `$elements` is always a jQuery object.
      $AttachGalleryToPhotoswipeElement.each(function() {
        var $pic     = $(this),
          getItems = function() {
            var items = [];
            $pic.find('a.photoswipe-item').each(function() {
              var $href   = $(this).attr('href'),
                $size   = $(this).data('size').split('x'),
                $width  = $size[0],
                $height = $size[1];

              var item = {
                src : $href,
                w   : $width,
                h   : $height
              };

              items.push(item);
            });
            return items;
          };

        var items = getItems();

        $.each(items, function(index, value) {
          image[index]     = new Image();
          image[index].src = value['src'];
        });

        $(once('ClickItemFromGallery', $pic)).on('click', 'a.photoswipe-item', function(event) {
          event.preventDefault();

          // Get the index of our parent which is part of the grid.
          // Filter out any non-images because they aren't in the carousel.
          var $index = $pic
            .find(".field--item:not(.field--item--file)")
            .index($(this).parent());
          var options = {
            index: $index,
            showHideOpacity: true,
            mainClass : 'pswp--minimal--dark',
            barsSize : {top: 0, bottom: 0},
            captionEl : false,
            fullscreenEl : false,
            shareEl : false,
            bgOpacity : 0.85,
            tapToClose : true,
            tapToToggleControls : false
          };

          var lightBox = new PhotoSwipe($pswp, PhotoSwipeUI_Default, items, options);
          lightBox.init();
        });
      });
    }
  };

})(Drupal, jQuery, once);

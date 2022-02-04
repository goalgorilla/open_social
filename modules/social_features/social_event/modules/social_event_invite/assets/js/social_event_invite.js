(function ($) {
  'use strict';

  Drupal.behaviors.socialEventnIvite = {
    attach: function () {
      $('#event-link-share').find('span').on('click touch', function () {
        var copyText = $('#event-link-share').find('input')[0];
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand('copy');
      });
    }
  }

})(jQuery);

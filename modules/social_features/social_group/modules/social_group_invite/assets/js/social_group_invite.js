(function ($) {
  'use strict';

  Drupal.behaviors.social_group_invite = {
    attach: function () {
      $('#group-link-share').find('span').on('click touch', function () {
        var copyText = $('#group-link-share').find('input')[0];
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand('copy');
      });
    }
  }

})(jQuery);

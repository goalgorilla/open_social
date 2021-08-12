(function ($, Drupal) {

  Drupal.behaviors.taggingPostCollapsle = {
    attach: function attach(context) {
      $('[id*="edit-social-tagging-settings"]', context).once('taggingPostCollapsle').each(function () {
        var $this = $(this);
        var btn = $this.find('.card__link');
        var body = $this.find('.panel-body');

        function switchMode($element) {
          var $icon = $element.find('svg');
          if ($icon.hasClass('icon-expand_more')) {
            $icon.html('<use xlink:href="#icon-expand-less" />');
          }
          else {
            $icon.html('<use xlink:href="#icon-expand_more" />');
          }

          $icon.toggleClass('icon-expand_more');

          return $element;
        }

        btn.on('click', function () {
          switchMode(btn);
          body.slideToggle(300);

          return false;
        });
      });
    }
  };

})(jQuery, Drupal);
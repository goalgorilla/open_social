(function ($, Drupal) {
  Drupal.behaviors.socialFollowUser = {
    attach: function attach(context) {
      $(context).find('.preview > img').once('preview').each(function (i, element) {
        var $element = $(element);
        var id = $element.parent().attr('id');

        Drupal.ajax({
          progress: false,
          dialogType: 'modal',
          dialog: {
            title: $element.attr('alt'),
            width: '50%',
            position: {
              my: 'left top',
              at: 'right top',
              of: '#' + id
            }
          },
          base: id,
          element: element,
          url: '/user/2/information',
          event: 'mouseover'
        });
      });
    }
  };
}(jQuery, Drupal));

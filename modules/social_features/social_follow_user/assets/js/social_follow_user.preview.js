(function ($, Drupal) {
  Drupal.behaviors.socialFollowUser = {
    attach: function attach(context) {
      var dialogs = [];
      var profiles = [];

      $(context).find('img[id^="profile-preview"]').on('mouseover', function () {
        var $element = $(this);
        var selector = $element.attr('id');
        var identifier = $element.data('profile');

        var dialog = function () {
          dialogs[selector] = Drupal.dialog(
            '<div>'.concat(profiles[identifier], '</div>'),
            {
              title: $element.attr('alt'),
              width: '50%',
              position: {
                my: 'left top',
                at: 'right top',
                of: '#' + selector
              },
              create: function () {
                var $wrapper = $(this).closest('.ui-dialog');

                $wrapper.find('.ui-dialog-titlebar-close').remove();

                $wrapper.on('mouseleave', function () {
                  dialogs[selector].close();
                });
              }
            }
          );

          console.log(dialogs[selector]);

          dialogs[selector].showModal();
        };

        if (dialogs[selector] !== undefined) {
          dialogs[selector].showModal();
        }
        else if (profiles[identifier] !== undefined) {
          dialog();
        }
        else {
          var ajax = Drupal.ajax({
            url: Drupal.url('user/' + $element.data('profile') + '/information')
          });

          ajax.commands.insert = function (ajax, response, status) {
            if (response.method === null) {
              profiles[identifier] = response.data;

              dialog();
            }
          };

          ajax.execute();
        }
      });
    }
  };
}(jQuery, Drupal));

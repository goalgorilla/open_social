(function ($, Drupal, window) {
  Drupal.behaviors.socialFollowUser = {
    attach: function attach(context) {
      var timeouts = [], dialogs = [], profiles = [];

      $(context).find('img[id^="profile-preview"]')
        .on('mouseover', function () {
          var $element = $(this);
          var selector = $element.attr('id');

          timeouts[selector] = window.setTimeout(function () {
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
                    $(this).closest('.ui-dialog')
                      .on('mouseleave', function () {
                        dialogs[selector].close();
                      })
                      .find('.ui-dialog-titlebar-close').remove();
                  }
                }
              );

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
          }, 200);
        })
        .on('mouseout', function () {
          window.clearTimeout(timeouts[$(this).attr('id')]);
        });
    }
  };
}(jQuery, Drupal, window));

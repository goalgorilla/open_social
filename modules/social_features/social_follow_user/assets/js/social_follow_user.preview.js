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
                  dialogClass: 'social-dialog',
                  width: '50%',
                  position: {
                    my: 'left top',
                    at: 'right top',
                    of: '#' + selector
                  },
                  create: function () {
                    $(this)
                      .closest('.ui-dialog')
                      .find('.ui-dialog-titlebar-close')
                      .remove();
                  },
                  open: function () {
                    $('.ui-widget-overlay').addClass('hide');
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
          var selector = $(this).attr('id');

          window.clearTimeout(timeouts[selector]);

          if (dialogs[selector] !== undefined && dialogs[selector].open) {
            dialogs[selector].close();
          }
        });
    }
  };
}(jQuery, Drupal, window));

(function ($, Drupal, window) {
  Drupal.behaviors.socialProfilePreview = {
    attach: function attach(context) {
      var timeouts = [], dialogs = [], profiles = [];
      var refresh = -1;
      var delay = 200;
      var delta = 0;

      $(context).find('.profile-preview')
        .each(function () {
          if ($(this).attr('id') === undefined) {
            $(this).attr('id', 'profile-preview-' + delta++);
          }
        })
        .on('mouseover', function () {
          var $element = $(this);
          var selector = $element.attr('id');

          if (timeouts[selector] !== undefined) {
            window.clearTimeout(timeouts[selector]);
          }

          timeouts[selector] = window.setTimeout(function () {
            var identifier = $element.data('profile');

            function dialog() {
              dialogs[selector] = Drupal.dialog(
                '<div>'.concat(profiles[identifier], '</div>'),
                {
                  dialogClass: 'social-dialog social-dialog--user-preview',
                  width: '384px',
                  position: {
                    my: 'left top',
                    at: 'right top',
                    of: $element,
                  },
                  create: function () {
                    $(this).closest('.ui-dialog')
                      .on('mouseover', function () {
                        window.clearTimeout(timeouts[selector]);
                      })
                      .on('mouseleave', function () {
                        timeouts[selector] = window.setTimeout(function () {
                          dialogs[selector].close();

                          if (refresh === 1) {
                            Object.entries(dialogs).forEach(([key, val]) => {
                              if (dialogs[key].deleted == true){
                                delete(dialogs[key]);
                              }
                            });
                          }
                        }, delay);
                      })
                      .find('.ui-dialog-titlebar-close').remove();
                  },
                  open: function () {
                    $(this).find('a.avatar').blur();
                    $('.ui-widget-overlay').addClass('hide');
                  }
                }
              );

              dialogs[selector].showModal();
              dialogs[selector].profile_id = $element.attr('data-profile');
            }

            if (dialogs[selector] !== undefined) {
              dialogs[selector].showModal();
              Drupal.ajax.bindAjaxLinks(document.body);
            }
            else {
              var ajax = Drupal.ajax({
                url: Drupal.url('profile/' + identifier + '/preview')
              });

              ajax.commands.insert = function (ajax, response, status) {
                if (response.method === null) {
                  profiles[identifier] = response.data;

                  dialog();
                }
              };

              ajax.execute();
            }
            // When page structure has been changed bind Ajax functionality.
            $(document).ajaxComplete(function(event, request, settings) {
              Drupal.ajax.bindAjaxLinks(document.body);
              refresh = settings.url.indexOf('flag');
              selector = $element.attr('id');

              if (dialogs[selector] !== undefined) {
                profile_id = dialogs[selector].profile_id;

                if (refresh === 1) {
                  Object.entries(dialogs).forEach(([key, val]) => {
                    if (val.profile_id === profile_id) {
                      dialogs[key].deleted = true;
                    }
                    else {
                      dialogs[key].deleted = false;
                    }
                  });
                }
              }
            });

          }, delay);
        })
        .on('mouseout', function () {
          var selector = $(this).attr('id');

          window.clearTimeout(timeouts[selector]);

          timeouts[selector] = window.setTimeout(function () {
            if (dialogs[selector] !== undefined && dialogs[selector].open) {
              dialogs[selector].close();
            }
          }, delay);
        });
    }
  };
}(jQuery, Drupal, window));

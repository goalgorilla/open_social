/**
 * @file
 * Scripts for the preview popup.
 */
(function (Drupal, $, once) {
  Drupal.behaviors.previewPopupBehavior = {
    attach: function (context) {
      var timeouts = [], dialogs = [], previewPopup = [];
      var refresh = -1;
      var delayOpen = 1000;
      var delayClose = 200;
      var delta = 0;

      $(once('previewPopupBehavior', $(context).find('[data-preview-url]')))
        .on('mouseover', function () {
          var $element = $(this);
          var selector = $element.attr('id');
          var url = $element.data('preview-url');
          console.log(url);

          if (timeouts[selector] !== undefined) {
            window.clearTimeout(timeouts[selector]);
          }

          timeouts[selector] = window.setTimeout(function () {
            var identifier = $element.data('preview-id');

            function dialog() {
              dialogs[selector] = Drupal.dialog(
                '<div>'.concat(previewPopup[identifier].data, '</div>'),
                {
                  dialogClass: 'social-dialog social-dialog--user-preview',
                  width: '360px',
                  position: {
                    my: 'left top',
                    at: 'right top',
                    of: $element,
                  },
                  create: function () {
                    var currentDialog = $(this).closest('.ui-dialog');

                    $(this).closest('.ui-dialog')
                      .on('mouseover', function () {
                        window.clearTimeout(timeouts[selector]);
                      })
                      .on('mouseleave', function () {
                        timeouts[selector] = window.setTimeout(function () {
                          // currentDialog.remove();

                          if (refresh === 1) {
                            cleanupUserData(dialogs);
                            cleanupUserData(previewPopup);
                          }
                        }, delayOpen);
                      })
                      .find('.ui-dialog-titlebar-close').remove();

                    // Clean up stored user data and display actual info.
                    var cleanupUserData = function (items) {
                      Object.entries(items).forEach(([key, val]) => {
                        if (items[key].deleted){
                          delete(items[key]);
                        }
                      });
                    };
                  },
                  open: function () {
                    $(this).find('a').blur();
                    $('.ui-widget-overlay').addClass('hide');
                  }
                }
              );

              dialogs[selector].showModal();
              dialogs[selector].popup_preview_id = $element.attr('data-preview-id');
            }

            if (dialogs[selector] !== undefined) {
              dialogs[selector].showModal();
              Drupal.ajax.bindAjaxLinks(document.body);
            }
            else if (
              previewPopup[identifier] !== undefined &&
              (previewPopup[identifier].deleted === false || previewPopup[identifier].deleted === undefined)
            ) {
              dialog();
              Drupal.ajax.bindAjaxLinks(document.body);
            }
            else {
              var ajax = Drupal.ajax({
                url: Drupal.url(url.substring(1))
              });

              ajax.commands.insert = function (ajax, response, status) {
                if (response.method === null) {
                  previewPopup[identifier] = {
                    data: response.data,
                    popup_preview_id: identifier
                  };
                }

                dialog();
              };

              ajax.execute();
            }
            // When page structure has been changed bind Ajax functionality.
            $(document).ajaxComplete(function(event, request, settings) {
              Drupal.ajax.bindAjaxLinks(document.body);
              refresh = settings.url.indexOf('flag');
              selector = $element.attr('id');

              var isActualUserData = function (items, id, refresh) {
                if (items[id] !== undefined) {
                  popup_preview_id = items[id].popup_preview_id;
                  if (refresh === 1) {
                    Object.entries(items).forEach(([key, val]) => {
                      items[key].deleted = val.popup_preview_id == popup_preview_id;
                    });
                  }
                }
              };

              // Flag/Unflag the user data which needs to be refreshed.
              isActualUserData(dialogs, selector, refresh);
              isActualUserData(previewPopup, identifier, refresh);
            });
          }, delayOpen);
        })
        .each(function () {
          if ($(this).attr('id') === undefined) {
            $(this).attr('id', 'preview-popup-' + delta++);
          }
        })
        .on('mouseout', function () {
          var selector = $(this).attr('id');

          window.clearTimeout(timeouts[selector]);

          timeouts[selector] = window.setTimeout(function () {
            if (dialogs[selector] !== undefined && dialogs[selector].open) {
              // $(context).find('.ui-dialog').remove();
            }
          }, delayClose);
        });
    }
  };

})(Drupal, jQuery, once);

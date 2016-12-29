(function ($) {

  'use strict';

  Drupal.behaviors.socialUserExport = {
    attach: function(context, settings) {
      $('#views-form-user-admin-people-page-1').once('socialUserExport').each(function (i, e) {
        var $selectAll = $('.views-field-user-bulk-form.select-all .form-checkbox', e),
            $action = $('.form-item-action .form-select', e),
            colspan = $('table th', e).length,
            $td = $('<td colspan="' + colspan + '" />'),
            $tr = $('<tr align="center" />').append($td).hide();

        var $selectAllPagesButton = $('<button type="button">' + Drupal.t('Select all @count items on all pages', {
            '@count': settings.socialUserExport.usersCount
          }) + '</button>')
          .appendTo($td);

        var $selectThisPageButton = $('<button type="button">' + Drupal.t('Select all items on this page') + '</button>')
          .hide()
          .appendTo($td);

        var updateState = function() {
          if ($action.val() != 'user_export_user_action') {
            $tr.hide();
            return;
          } else if (!$selectAll.prop('checked')) {
            $tr.hide();
            $('#select-all').val(0);
            $selectAllPagesButton.show();
            $selectThisPageButton.hide();
            return;
          }

          $tr.show();
        };

        $selectAllPagesButton.on('click', function () {
          $('#select-all').val(1);
          $(this).hide();
          $selectThisPageButton.show();
          updateState();
        });

        $selectThisPageButton.on('click', function () {
          $('#select-all').val(0);
          $selectAllPagesButton.show();
          $(this).hide();
          updateState();
        });

        $('table tbody', e).prepend($tr);

        $selectAll.on('change', updateState);
        $action.on('change', updateState);
      });
    }
  };
})(jQuery);

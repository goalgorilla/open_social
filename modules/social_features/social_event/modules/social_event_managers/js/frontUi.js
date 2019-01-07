/**
 * @file
 * Select-All Button functionality.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.viewsBulkOperationsFrontUi = {
    attach: function (context, settings) {
      var $primarySelectAll = $('.select-all', '.vbo-view-form');
      $primarySelectAll.addClass('form-no-label checkbox form-checkbox');

      // Click handler on clicking select all across pages.
      $('.views-table-row-vbo-select-all .form-submit').on('click', function () {
        // Put in a message for all selected users.
        if ($('.vbo-select-all').prop('checked') === true) {
          if ($('.temporary-placeholder.panel-heading').length < 1) {
            var message = Drupal.t('<b>All members across all pages</b> are selected.');
            $('#vbo-action-form-wrapper').append('<div class="temporary-placeholder card__block">' + message +  '</span>');
          }
        }
        else {
          $('.temporary-placeholder').remove();
        }
      });
    }
  };

  /**
   * Perform an AJAX request to update selection.
   *
   * @param {bool} state
   * @param {string} value
   */
  Drupal.viewsBulkOperationsSelection.update = function (state, index, value) {
    if (value === undefined) {
      value = null;
    }
    if (this.view_id.length && this.display_id.length) {
      var list = {};
      if (value && value != 'on') {
        list[value] = this.list[index][value];
      }
      else {
        list = this.list[index];
      }
      var op = state ? 'remove' : 'add';

      var $placeholder = this.$placeholder;
      var target_uri = '/' + drupalSettings.path.pathPrefix + 'views-bulk-operations/ajax/' + this.view_id + '/' + this.display_id;
      $.ajax(target_uri, {
        method: 'POST',
        data: {
          list: list,
          op: op
        },
        success: function (data) {
          var count = parseInt($placeholder.text());
          count += data.change;

          $placeholder.html(Drupal.formatPlural(count, '<b>@count enrollee</b> is selected','<b>@count enrollees</b> are selected'));
        }
      });
    }
  };

})(jQuery, Drupal);

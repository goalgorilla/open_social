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

      // Make sure we set the Actions button to relative when the wrapper gets
      // hidden by VBO.
      $('.views-table-row-vbo-select-all .form-submit').on('click', function () {
        if ($('#vbo-action-form-wrapper .btn-group.dropdown').css("position") === "absolute") {
          $('#vbo-action-form-wrapper .btn-group.dropdown').css({position:'relative', padding:'1rem'});
        }
        else if ($('#vbo-action-form-wrapper .btn-group.dropdown').css("position") === "relative") {
          $('#vbo-action-form-wrapper .btn-group.dropdown').css({position:'absolute', padding:'10px 0'});
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

          if (!Number.isInteger(count)) {
            count = 1;
          }

          if (count > 1) {
            $placeholder.html(Drupal.t('<b>@count Members</b> are selected', {
              '@count': count
            }));
          }
          else if (count === 0) {
            $placeholder.html(Drupal.t('<b>no members</b> are selected', {
              '@count': count
            }));
          }
          else {
            $placeholder.html(Drupal.t('<b>@count Member</b> is selected', {
              '@count': count
            }));
          }
        }
      });
    }
  };

})(jQuery, Drupal);

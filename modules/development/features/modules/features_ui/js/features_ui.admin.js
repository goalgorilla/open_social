/**
 * jQuery.fn.sortElements
 * --------------
 * @param Function comparator:
 *   Exactly the same behaviour as [1,2,3].sort(comparator)
 *
 * @param Function getSortable
 *   A function that should return the element that is
 *   to be sorted. The comparator will run on the
 *   current collection, but you may want the actual
 *   resulting sort to occur on a parent or another
 *   associated element.
 *
 *   E.g. $('td').sortElements(comparator, function(){
 *      return this.parentNode;
 *   })
 *
 *   The <td>'s parent (<tr>) will be sorted instead
 *   of the <td> itself.
 *
 * Credit: http://james.padolsey.com/javascript/sorting-elements-with-jquery/
 *
 */
jQuery.fn.sortElements = (function () {

  "use strict";

  var sort = [].sort;

  return function (comparator, getSortable) {

    getSortable = getSortable || function () {return this;};

    var placements = this.map(function () {

      var sortElement = getSortable.call(this);
      var parentNode = sortElement.parentNode;

      // Since the element itself will change position, we have
      // to have some way of storing its original position in
      // the DOM. The easiest way is to have a 'flag' node:
      var nextSibling = parentNode.insertBefore(
          document.createTextNode(''),
          sortElement.nextSibling
        );

      return function () {

        if (parentNode === this) {
          throw new Error(
            "You can't sort elements if any one is a descendant of another."
          );
        }

        // Insert before flag:
        parentNode.insertBefore(this, nextSibling);
        // Remove flag:
        parentNode.removeChild(nextSibling);

      };

    });

    return sort.call(this, comparator).each(function (i) {
      placements[i].call(getSortable.call(this));
    });

  };

})();

(function ($) {

  "use strict";

  Drupal.behaviors.features = {
    attach: function (context) {

      // mark any conflicts with a class
      if ((typeof drupalSettings.features !== 'undefined') && (typeof drupalSettings.features.conflicts !== 'undefined')) {
      //  for (var configType in drupalSettings.features.conflicts) {
          if (drupalSettings.features.conflicts) {
            var configConflicts = drupalSettings.features.conflicts;
            $('#features-export-wrapper input[type=checkbox]', context).each(function () {
              if (!$(this).hasClass('features-checkall')) {
                var key = $(this).attr('name');
                var matches = key.match(/^([^\[]+)(\[.+\])?\[(.+)\]\[(.+)\]$/);
                var component = matches[1];
                var item = matches[4];
                if ((component in configConflicts) && (item in configConflicts[component])) {
                  $(this).parent().addClass('component-conflict');
                }
              }
            });
          }
        //}
      }

      function _checkAll(value) {
        if (value) {
          $('#features-export-wrapper .component-select input[type=checkbox]:visible', context).each(function () {
            var move_id = $(this).attr('id');
            $(this).click();
            $('#'+ move_id).prop('checked', true);
          });
        }
        else {
          $('#features-export-wrapper .component-added input[type=checkbox]:visible', context).each(function () {
            var move_id = $(this).attr('id');
            $(this).click();
            $('#'+ move_id).prop('checked', false);
          });
        }
      }

      function updateComponentCountInfo(item, section) {
        var parent;

        switch (section) {
          case 'select':
            parent = $(item).closest('.features-export-list').siblings('.features-export-component');
            $('.component-count', parent).text(function (index, text) {
                return +text + 1;
              }
            );
            break;
          case 'added':
          case 'detected':
            parent = $(item).closest('.features-export-component');
            $('.component-count', parent).text(function (index, text) {
              return text - 1;
            });
        }
      }

      function moveCheckbox(item, section, value) {
        updateComponentCountInfo(item, section);
        var curParent = item;
        if ($(item).hasClass('form-type-checkbox')) {
          item = $(item).children('input[type=checkbox]');
        }
        else {
          curParent = $(item).parents('.form-type-checkbox');
        }
        var newParent = $(curParent).parents('.features-export-parent').find('.component-'+section+' .form-checkboxes');
        $(curParent).detach();
        $(curParent).appendTo(newParent);
        var list = ['select', 'added', 'detected', 'included'];
        for (var i in list) {
          if (list[i]) {
            $(curParent).removeClass('component-' + list[i]);
            $(item).removeClass('component-' + list[i]);
          }
        }
        $(curParent).addClass('component-'+section);
        $(item).addClass('component-'+section);
        if (value) {
          $(item).attr('checked', 'checked');
        }
        else {
          $(item).removeAttr('checked');
        }
        $(newParent).parents('.component-list').removeClass('features-export-empty');

        // re-sort new list of checkboxes based on labels
        $(newParent).find('label').sortElements(
          function (a, b) {
            return $(a).text() > $(b).text() ? 1 : -1;
          },
          function () {
            return this.parentNode;
          }
        );
      }

      // provide timer for auto-refresh trigger
      var timeoutID = 0;
      var inTimeout = 0;
      function _triggerTimeout() {
        timeoutID = 0;
        _updateDetected();
      }
      function _resetTimeout() {
        inTimeout++;
        // if timeout is already active, reset it
        if (timeoutID !== 0) {
          window.clearTimeout(timeoutID);
          if (inTimeout > 0) { inTimeout--; }
        }
        timeoutID = window.setTimeout(_triggerTimeout, 500);
      }

      function _updateDetected() {
        if (!drupalSettings.features.autodetect) { return; }
        // query the server for a list of components/items in the feature and update
        // the auto-detected items
        var items = [];  // will contain a list of selected items exported to feature
        var components = {};  // contains object of component names that have checked items
        $('#features-export-wrapper input[type=checkbox]:checked', context).each(function () {
          if (!$(this).hasClass('features-checkall')) {
            var key = $(this).attr('name');
            var matches = key.match(/^([^\[]+)(\[.+\])?\[(.+)\]\[(.+)\]$/);
            components[matches[1]] = matches[1];
            if (!$(this).hasClass('component-detected')) {
              items.push(key);
            }
          }
        });
        var featureName = $('#edit-machine-name').val();
        if (featureName === '') {
          featureName = '*';
        }

        var url = Drupal.url('features/api/detect/' + featureName);
        var excluded = drupalSettings.features.excluded;
        var required = drupalSettings.features.required;
        var postData = {'items': items, 'excluded': excluded, 'required': required};
        jQuery.post(url, postData, function (data) {
          if (inTimeout > 0) { inTimeout--; }
          // if we have triggered another timeout then don't update with old results
          if (inTimeout === 0) {
            // data is an object keyed by component listing the exports of the feature
            for (var component in data) {
              if (data[component]) {
                var itemList = data[component];
                $('#features-export-wrapper .component-' + component + ' input[type=checkbox]', context).each(function () {
                  var key = $(this).attr('value');
                  // first remove any auto-detected items that are no longer in component
                  if ($(this).hasClass('component-detected')) {
                    if (!(key in itemList)) {
                      moveCheckbox(this, 'select', false);
                    }
                  }
                  // next, add any new auto-detected items
                  else if ($(this).hasClass('component-select')) {
                    if (key in itemList) {
                      moveCheckbox(this, 'detected', itemList[key]);
                      $(this).prop('checked', true);
                      $(this).parent().show(); // make sure it's not hidden from filter
                    }
                  }
                });
              }
            }
            // loop over all selected components and check for any that have been completely removed
            for (var selectedComponent in components) {
              if ((data == null) || !(selectedComponent in data)) {
                $('#features-export-wrapper .component-' + selectedComponent + ' input[type=checkbox].component-detected', context).each(moveCheckbox(this, 'select', false));
              }
            }
          }
        }, "json");
      }

      // Handle component selection UI
      $('#features-export-wrapper input[type=checkbox]', context).click(function () {
        _resetTimeout();
        if ($(this).hasClass('component-select')) {
          moveCheckbox(this, 'added', true);
        }
        else if ($(this).hasClass('component-included')) {
          moveCheckbox(this, 'added', false);
        }
        else if ($(this).hasClass('component-added')) {
          if ($(this).is(':checked')) {
            moveCheckbox(this, 'included', true);
          }
          else {
            moveCheckbox(this, 'select', false);
          }
        }
      });

      // Handle select/unselect all
      $('#features-filter .features-checkall.form-checkbox', context).click(function () {
        if ($(this).prop('checked')) {
          _checkAll(true);
          $(this).next().html(Drupal.t('Deselect all'));
        }
        else {
          _checkAll(false);
          $(this).next().html(Drupal.t('Select all'));
        }
        _resetTimeout();
      });

      // Handle filtering

      // provide timer for auto-refresh trigger
      var filterTimeoutID = 0;
      function _triggerFilterTimeout() {
        filterTimeoutID = 0;
        _updateFilter();
      }
      function _resetFilterTimeout() {
        // if timeout is already active, reset it
        if (filterTimeoutID !== 0) {
          window.clearTimeout(filterTimeoutID);
          filterTimeoutID = null;
        }
        filterTimeoutID = window.setTimeout(_triggerFilterTimeout, 200);
      }
      function _updateFilter() {
        var filter = $('#features-filter input').val();
        var regex = new RegExp(filter, 'i');
        // collapse fieldsets
        var newState = {};
        var currentState = {};
        $('#features-export-wrapper details.features-export-component', context).each(function () {
          // expand parent fieldset
          var section = $(this).attr('id');
          var details = $(this);

          currentState[section] = details.prop('open');
          if (!(section in newState)) {
            newState[section] = false;
          }

          details.find('.form-checkboxes label').each(function () {
            if (filter === '') {
              // collapse the section, but make checkbox visible
              if (currentState[section]) {
                details.prop('open', false);
                currentState[section] = false;
              }
              $(this).parent().show();
            }
            else if ($(this).text().match(regex)) {
              $(this).parent().show();
              newState[section] = true;
            }
            else {
              $(this).parent().hide();
            }
          });
        });
        for (var section in newState) {
          if (currentState[section] !== newState[section]) {
            if (newState[section]) {
              $('#'+section).prop('open', true);
            }
            else {
              $('#'+section).prop('open', false);
            }
          }
        }
      }
      $('#features-filter input', context).bind("input", function () {
        _resetFilterTimeout();
      });
      $('#features-filter .features-filter-clear', context).click(function () {
        $('#features-filter input').val('');
        _updateFilter();
      });

      // show the filter bar
      $('#features-filter', context).removeClass('element-invisible');

      // handle Package selection checkboxes in the Differences page
      $('.features-diff-listing .features-diff-header input.form-checkbox', context).click(function () {
        var value = $(this).prop('checked');
        $('.features-diff-listing .diff-'+$(this).prop('value')+' input.form-checkbox', context).each(function () {
          $(this).prop('checked', value);
          if (value) {
            $(this).parents('tr').addClass('selected');
          }
          else {
            $(this).parents('tr').removeClass('selected');
          }
        });
      });

      // handle special theming of headers in tableselect
      $('td.features-export-header-row', context).each(function () {
        var row = $(this).parent('tr');
        row.addClass('features-export-header-row');
        var checkbox = row.find('td input:checkbox');
        if (checkbox.length) {
          checkbox.hide();
        }
      });

      // handle clicking anywhere in row on Differences page
      $('.features-diff-listing tr td:nth-child(2)', context).click(function () {
        var checkbox = $(this).parent().find('td input:checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).triggerHandler('click');
        if (checkbox.prop('checked')) {
          $(this).parents('tr').addClass('selected');
        }
        else {
          $(this).parents('tr').removeClass('selected');
        }
      });
      $('.features-diff-listing thead th:nth-child(2)', context).click(function () {
        var checkbox = $(this).parent().find('th input:checkbox');
        checkbox.click();
      });
    }
  };

})(jQuery);

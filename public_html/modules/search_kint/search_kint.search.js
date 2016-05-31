/**
 * @file
 * Add functionality to be able to search though one or more kint dumps.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.searchKintSearch = {
    attach: function (context, settings) {

      // Define kint root.
      var kint = $('.kint:not(".processed")');

      // Check if there is a kint.
      if ($(kint).addClass('processed').length > 0) {
        // Create the search kint form.
        var form = '<div class="search-kint">';
        form += '  <form id="search-kint">';
        form += '    <input class="form-text" type="text" name="search-query" />';
        // If there are more than one kint's.
        if ($(kint).length > 1) {
          form += '    <select class="form-select" name="search-option">';
          form += '      <option value="all">' + Drupal.t('Search all') + '</option>';
          // For each kint.
          $(kint).each(function (i) {
            i++;
            form += '      <option value="' + i + '">' + Drupal.t('Search kint') + ' #' + i + '</option>';
          });
          form += '    </select>';
        }
        form += '    <input class="form-submit" type="submit" value="' + Drupal.t('Submit') + '" name="submit" />';
        form += '  </form>';
        form += '</div>';
        form += '<div class="search-kint-results"></div>';

        // Insert the form before the first kint.
        kint.eq(0).before(form);
      }

      // On submit execute the following.
      $('form#search-kint').submit(function (e) {
        // Remove result and classes from previous query.
        $('.kint-query-result').removeClass('kint-query-result');
        $('.kint-parent.kint-show').removeClass('kint-show');
        var $search_kint_result = $('.search-kint-results');
        $search_kint_result.html('');

        // Get query value and option value as variables.
        var query = $('input[name=search-query]', this).val();
        var option = $('select[name=search-option]', this).val();

        // If the query is not empty, we can proceed.
        if (query) {
          var search_kint;
          // Check if we're just trying to look through a single kint.
          if (option && option !== 'all') {
            search_kint = $('.kint').eq(option - 1);
          }
          else {
            // Let's search through all of them!
            search_kint = $('.kint');
          }

          // Find all elements with the query.
          var count = 0;
          $('dt:contains(' + query + ')', search_kint).each(function () {
            count++;

            // Add result class.
            $(this).parent().addClass('kint-query-result');

            // Expand parents until the query result is layered open before the user.
            $(this).parentsUntil('.kint', 'dd').prev().addClass('kint-show');

          });
          // Show result overview.
          $search_kint_result.html(Drupal.formatPlural(count, 'Found 1 element', 'Found @count elements'));
        }
        else {
          $search_kint_result.html(Drupal.t('Empty query'));
        }

        // Prevent the form from being submitted.
        e.preventDefault();
      });

    }
  };
})(jQuery);

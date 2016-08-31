/**
 * @file
 */

(function ($) {
    Drupal.behaviors.download_count = {
        attach: function (context, settings) {
            $('#download-count-export-form div.form-item-download-count-export-date-range-from').hide();
            $('#download-count-export-form div.form-item-download-count-export-date-range-to').hide();

            $('input#edit-download-count-export-range-0').bind('click', function () {
                    $('#download-count-export-form div.form-item-download-count-export-date-range-from').hide();
                    $('#download-count-export-form div.form-item-download-count-export-date-range-to').hide();
            }
            )

            $('input#edit-download-count-export-range-1').bind('click', function () {
                    $('#download-count-export-form div.form-item-download-count-export-date-range-from').show();
                    $('#download-count-export-form div.form-item-download-count-export-date-range-to').show();
            }
            )
        }
  }
})(jQuery);

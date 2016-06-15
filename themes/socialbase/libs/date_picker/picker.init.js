(function ($) {

  Drupal.behaviors.initDatepicker = {
    attach: function (context, settings) {

      $('.datepicker').pickadate({selectYears: 20, format: 'dd mmm yyyy', formatSubmit: 'yyyy-mm-dd', close: 'Done', CloseOnSelect: true});
      $('.timepicker').pickatime({format: 'H:i', closeOnSelect: true, closeOnClear: true, formatSubmit: 'H:i'})

    }
  }

})(jQuery);

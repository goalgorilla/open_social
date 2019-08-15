(function ($) {

  'use strict';

  Drupal.behaviors.initSelect2 = {
    attach: function (context, settings) {
      $('select[multiple]', context).once('select2').each(function (i, e) {
        var options = {
          theme: 'social',
          placeholder: Drupal.t('Select an option'),
          width: '100%',
        };

        if (!isNaN(parseInt($(e).attr('maxlength'), 10))) {
          options.maximumSelectionLength = $(e).attr('maxlength');
        }

        $(this).select2(options);
        $(this).on('change', function () {
          var value = $(this).val(),
            key = $.inArray('_none', value);

          if (!value && $('[value="_none"]', this).length) {
            value = ['_none'];
            $(this)
              .val(value)
              .trigger('change.select2');
          } else if (value && value.length > 1 && key > -1) {
            value.splice(key, 1);
            $(this)
              .val(value)
              .trigger('change.select2');
          }
        });
      });
    }
  };

})(jQuery);
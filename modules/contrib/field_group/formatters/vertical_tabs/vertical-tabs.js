(function ($) {

  'use strict';

  /**
   * Implements Drupal.FieldGroup.processHook().
   */
  Drupal.FieldGroup.Effects.processTabs = {
    execute: function (context, settings, type) {
      if (type === 'form') {
        // Add required fields mark to any fieldsets containing required fields
        $('details.vertical-tabs-pane', context).once('fieldgroup-effects', function (i) {
          if ($(this).is('.required-fields') && $(this).find('.form-required').length > 0) {
            $(this).data('verticalTab').link.find('strong:first').after($('.form-required').eq(0).clone()).after(' ');
          }
          if ($('.error', $(this)).length) {
            $(this).data('verticalTab').link.parent().addClass('error');
            Drupal.FieldGroup.setGroupWithfocus($(this));
            $(this).data('verticalTab').focus();
          }
        });
      }
    }
  };

})(jQuery);

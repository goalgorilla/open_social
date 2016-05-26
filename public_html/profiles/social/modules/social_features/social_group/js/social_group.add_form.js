(function ($) {

  /**
   * Behaviors.
   */
  Drupal.behaviors.socialGroupAddForm = {
    attach: function (context, settings) {
      // Get "Description" textarea of Group Create form.
      var textarea = $('#edit-field-group-description-0-value', context);
      // Insert new counter element after textarea.
      $('<div/>').addClass('counter').insertAfter($(textarea));
      // Init jQuery Simply Countable plugin
      $(textarea).simplyCountable({
        counter: '.counter',
        countType: 'characters',
        maxCount: 200,
        countDirection: 'down',
        safeClass: 'safe',
        overClass: 'over',
      });
    }
  };

})(jQuery);
